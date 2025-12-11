<?php

namespace Karyalay\Services;

use Karyalay\Models\User;
use Karyalay\Models\Session;
use Karyalay\Models\PasswordResetToken;

/**
 * Authentication Service
 * 
 * Handles user authentication, registration, and password management
 */
class AuthService
{
    private User $userModel;
    private Session $sessionModel;
    private PasswordResetToken $passwordResetTokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->sessionModel = new Session();
        $this->passwordResetTokenModel = new PasswordResetToken();
    }

    /**
     * Register a new user
     * 
     * @param array $data User registration data (email, password, name, phone [required], business_name)
     * @return array Returns ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function register(array $data): array
    {
        // Validate required fields
        $requiredFields = ['email', 'password', 'name'];
        
        // Phone is required for customer registrations
        $role = $data['role'] ?? 'CUSTOMER';
        if ($role === 'CUSTOMER') {
            $requiredFields[] = 'phone';
        }
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields = array_filter($requiredFields, function($f) use ($data) {
                    return empty($data[$f]);
                });
                $fieldNames = array_map(function($f) {
                    return $f === 'phone' ? 'phone number' : $f;
                }, $missingFields);
                
                return [
                    'success' => false,
                    'user' => null,
                    'error' => ucfirst(implode(', ', $fieldNames)) . ' ' . (count($fieldNames) > 1 ? 'are' : 'is') . ' required'
                ];
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Invalid email format'
            ];
        }

        // Validate phone number format (if provided)
        // Accepts international format with ISD code: +[country code][number]
        if (!empty($data['phone']) && !preg_match('/^\+[0-9]{1,4}[0-9]{6,15}$/', $data['phone'])) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Please enter a valid phone number with country code'
            ];
        }

        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Email already exists'
            ];
        }

        // Validate password strength (minimum 8 characters)
        if (strlen($data['password']) < 8) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Password must be at least 8 characters'
            ];
        }

        // Create user
        $user = $this->userModel->create([
            'email' => $data['email'],
            'password' => $data['password'], // Will be hashed in User model
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'business_name' => $data['business_name'] ?? null,
            'role' => $role,
            'email_verified' => false
        ]);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Failed to create user'
            ];
        }

        // Send welcome email to user
        try {
            $emailService = new EmailService();
            $emailService->sendWelcomeEmail($data['email'], $data['name']);
        } catch (\Exception $e) {
            error_log('Failed to send welcome email: ' . $e->getMessage());
            // Don't fail registration if email fails
        }

        // Send notification email to admin
        try {
            $emailService = new EmailService();
            $emailService->sendNewUserNotification([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $user['role'],
                'business_name' => $data['business_name'] ?? null,
                'email_verified' => $user['email_verified']
            ]);
        } catch (\Exception $e) {
            error_log('Failed to send admin notification email: ' . $e->getMessage());
            // Don't fail registration if email fails
        }

        // Remove password_hash from response
        unset($user['password_hash']);

        return [
            'success' => true,
            'user' => $user,
            'error' => null
        ];
    }

    /**
     * Login user with email and password
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array Returns ['success' => bool, 'user' => array|null, 'session' => array|null, 'error' => string|null]
     */
    public function login(string $email, string $password): array
    {
        // Validate credentials
        $user = $this->userModel->verifyPassword($email, $password);

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'session' => null,
                'error' => 'Invalid email or password'
            ];
        }

        // Create session
        $session = $this->sessionModel->create($user['id'], 24); // 24 hours

        if (!$session) {
            return [
                'success' => false,
                'user' => null,
                'session' => null,
                'error' => 'Failed to create session'
            ];
        }

        // Remove password_hash from response
        unset($user['password_hash']);

        return [
            'success' => true,
            'user' => $user,
            'session' => $session,
            'error' => null
        ];
    }

    /**
     * Logout user by deleting session
     * 
     * @param string $token Session token
     * @return bool Returns true on success, false on failure
     */
    public function logout(string $token): bool
    {
        return $this->sessionModel->deleteByToken($token);
    }

    /**
     * Validate session token
     * 
     * @param string $token Session token
     * @return array|false Returns session data with user if valid, false otherwise
     */
    public function validateSession(string $token)
    {
        $session = $this->sessionModel->validate($token);

        if (!$session) {
            return false;
        }

        // Get user data
        $user = $this->userModel->findById($session['user_id']);

        if (!$user) {
            return false;
        }

        // Remove password_hash from response
        unset($user['password_hash']);

        return [
            'session' => $session,
            'user' => $user
        ];
    }

    /**
     * Request password reset
     * 
     * @param string $email User email
     * @return array Returns ['success' => bool, 'token' => array|null, 'error' => string|null]
     */
    public function requestPasswordReset(string $email): array
    {
        // Find user by email
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            // Don't reveal if email exists or not for security
            return [
                'success' => true,
                'token' => null,
                'error' => null
            ];
        }

        // Create password reset token (expires in 1 hour)
        $token = $this->passwordResetTokenModel->create($user['id'], 1);

        if (!$token) {
            return [
                'success' => false,
                'token' => null,
                'error' => 'Failed to create password reset token'
            ];
        }

        return [
            'success' => true,
            'token' => $token,
            'error' => null
        ];
    }

    /**
     * Reset password using token
     * 
     * @param string $token Password reset token
     * @param string $newPassword New password
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        // Validate token
        $resetToken = $this->passwordResetTokenModel->validate($token);

        if (!$resetToken) {
            return [
                'success' => false,
                'error' => 'Invalid or expired token'
            ];
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'error' => 'Password must be at least 8 characters'
            ];
        }

        // Update user password
        $updated = $this->userModel->update($resetToken['user_id'], [
            'password' => $newPassword // Will be hashed in User model
        ]);

        if (!$updated) {
            return [
                'success' => false,
                'error' => 'Failed to update password'
            ];
        }

        // Delete the used token
        $this->passwordResetTokenModel->delete($resetToken['id']);

        // Delete all sessions for this user (force re-login)
        $this->sessionModel->deleteByUserId($resetToken['user_id']);

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Change password for authenticated user
     * 
     * @param string $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array Returns ['success' => bool, 'error' => string|null]
     */
    public function changePassword(string $userId, string $currentPassword, string $newPassword): array
    {
        // Get user
        $user = $this->userModel->findById($userId);

        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found'
            ];
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => 'Current password is incorrect'
            ];
        }

        // Validate new password strength
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'error' => 'New password must be at least 8 characters'
            ];
        }

        // Update password
        $updated = $this->userModel->update($userId, [
            'password' => $newPassword // Will be hashed in User model
        ]);

        if (!$updated) {
            return [
                'success' => false,
                'error' => 'Failed to update password'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }
}

