<?php

/**
 * Authentication Helper Functions
 * 
 * Provides convenient functions for authentication operations
 */

use Karyalay\Services\AuthService;
use Karyalay\Services\RoleService;

/**
 * Start secure PHP session
 * 
 * @return void
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $config = require __DIR__ . '/../config/app.php';
        
        // Set secure session parameters
        ini_set('session.cookie_httponly', $config['session']['cookie_httponly'] ? '1' : '0');
        ini_set('session.cookie_secure', $config['session']['cookie_secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $config['session']['cookie_samesite']);
        ini_set('session.cookie_path', '/');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        session_name($config['session']['cookie_name']);
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Check if user is authenticated
 * 
 * @return bool Returns true if user is authenticated, false otherwise
 */
function isAuthenticated(): bool
{
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
}

/**
 * Get current authenticated user
 * 
 * @return array|null Returns user data or null if not authenticated
 */
function getCurrentUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }
    
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user ID
 * 
 * @return string|null Returns user ID or null if not authenticated
 */
function getCurrentUserId(): ?string
{
    if (!isAuthenticated()) {
        return null;
    }
    
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current session token
 * 
 * @return string|null Returns session token or null if not authenticated
 */
function getSessionToken(): ?string
{
    if (!isAuthenticated()) {
        return null;
    }
    
    return $_SESSION['session_token'] ?? null;
}

/**
 * Set authenticated user session
 * 
 * @param array $user User data
 * @param string $sessionToken Session token
 * @return void
 */
function setAuthenticatedUser(array $user, string $sessionToken): void
{
    startSecureSession();
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    $_SESSION['user_role'] = $user['role'] ?? 'CUSTOMER';
    $_SESSION['user_name'] = $user['name'] ?? '';
    $_SESSION['session_token'] = $sessionToken;
    $_SESSION['created'] = time();
}

/**
 * Clear authenticated user session
 * 
 * @return void
 */
function clearAuthenticatedUser(): void
{
    startSecureSession();
    
    // Clear session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['user']);
    unset($_SESSION['session_token']);
    unset($_SESSION['created']);
    
    // Destroy session
    session_destroy();
}

/**
 * Require authentication - redirect to login if not authenticated
 * 
 * @param string $redirectUrl URL to redirect to after login (default: current page)
 * @return void
 */
function requireAuth(string $redirectUrl = ''): void
{
    if (!isAuthenticated()) {
        if (empty($redirectUrl)) {
            $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/';
        }
        
        $_SESSION['redirect_after_login'] = $redirectUrl;
        
        // Use redirect helper for proper URL generation
        if (!function_exists('redirect')) {
            require_once __DIR__ . '/template_helpers.php';
        }
        redirect('/login.php');
    }
}

/**
 * Require specific role - redirect or show error if user doesn't have role
 * 
 * @param string|array $roles Required role(s)
 * @param bool $redirect Whether to redirect (true) or return false (false)
 * @return bool Returns true if user has role, false otherwise (if not redirecting)
 */
function requireRole($roles, bool $redirect = true): bool
{
    if (!isAuthenticated()) {
        if ($redirect) {
            header('Location: /login.php');
            exit;
        }
        return false;
    }
    
    $user = getCurrentUser();
    $userRole = $user['role'] ?? '';
    
    $requiredRoles = is_array($roles) ? $roles : [$roles];
    
    if (!in_array($userRole, $requiredRoles)) {
        if ($redirect) {
            http_response_code(403);
            echo "Access denied. You do not have permission to access this page.";
            exit;
        }
        return false;
    }
    
    return true;
}

/**
 * Check if user has specific role
 * Uses RoleService to check the user_roles table for multi-role support
 * 
 * @param string|array $roles Role(s) to check
 * @return bool Returns true if user has any of the specified roles, false otherwise
 */
function hasRole($roles): bool
{
    if (!isAuthenticated()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user || !isset($user['id'])) {
        return false;
    }
    
    $checkRoles = is_array($roles) ? $roles : [$roles];
    
    // Use RoleService to check roles from user_roles table
    return RoleService::userHasAnyRole($user['id'], $checkRoles);
}

/**
 * Check if user is admin
 * 
 * @return bool Returns true if user is admin, false otherwise
 */
function isAdmin(): bool
{
    return hasRole(['ADMIN', 'SUPER_ADMIN']);
}

/**
 * Check if user is customer
 * 
 * @return bool Returns true if user is customer, false otherwise
 */
function isCustomer(): bool
{
    return hasRole('CUSTOMER');
}

/**
 * Login user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array Returns ['success' => bool, 'user' => array|null, 'error' => string|null]
 */
function loginUser(string $email, string $password): array
{
    $authService = new AuthService();
    $result = $authService->login($email, $password);
    
    if ($result['success']) {
        setAuthenticatedUser($result['user'], $result['session']['token']);
    }
    
    return $result;
}

/**
 * Logout user
 * 
 * @return bool Returns true on success, false on failure
 */
function logoutUser(): bool
{
    $token = getSessionToken();
    
    if ($token) {
        $authService = new AuthService();
        $authService->logout($token);
    }
    
    clearAuthenticatedUser();
    
    return true;
}

/**
 * Register new user
 * 
 * @param array $data User registration data
 * @return array Returns ['success' => bool, 'user' => array|null, 'error' => string|null]
 */
function registerUser(array $data): array
{
    $authService = new AuthService();
    return $authService->register($data);
}

/**
 * Request password reset
 * 
 * @param string $email User email
 * @return array Returns ['success' => bool, 'token' => array|null, 'error' => string|null]
 */
function requestPasswordReset(string $email): array
{
    $authService = new AuthService();
    return $authService->requestPasswordReset($email);
}

/**
 * Reset password using token
 * 
 * @param string $token Password reset token
 * @param string $newPassword New password
 * @return array Returns ['success' => bool, 'error' => string|null]
 */
function resetPassword(string $token, string $newPassword): array
{
    $authService = new AuthService();
    return $authService->resetPassword($token, $newPassword);
}

/**
 * Change password for authenticated user
 * 
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return array Returns ['success' => bool, 'error' => string|null]
 */
function changePassword(string $currentPassword, string $newPassword): array
{
    $userId = getCurrentUserId();
    
    if (!$userId) {
        return [
            'success' => false,
            'error' => 'User not authenticated'
        ];
    }
    
    $authService = new AuthService();
    return $authService->changePassword($userId, $currentPassword, $newPassword);
}

/**
 * Guard customer portal route
 * Requires authentication and CUSTOMER or ADMIN role
 * Redirects to login if not authenticated, shows 403 if wrong role
 * 
 * @param string $redirectUrl URL to redirect to if not authenticated (relative path)
 * @return array Returns authenticated user data
 */
function guardCustomerPortal(string $redirectUrl = '/login.php'): array
{
    requireAuth();
    
    $user = getCurrentUser();
    
    if (!$user) {
        if (!function_exists('redirect')) {
            require_once __DIR__ . '/template_helpers.php';
        }
        redirect($redirectUrl);
    }

    // Allow both CUSTOMER and ADMIN roles to access customer portal
    if (!hasRole(['CUSTOMER', 'ADMIN'])) {
        http_response_code(403);
        echo "Access denied. Customer account required.";
        exit;
    }

    return $user;
}

/**
 * Guard admin route
 * Requires authentication and ADMIN role
 * Redirects to login if not authenticated, shows 403 if not admin
 * 
 * @param string $redirectUrl URL to redirect to if not authenticated
 * @return array Returns authenticated admin user data
 */
function guardAdmin(string $redirectUrl = '/login.php'): array
{
    requireAuth();
    
    $user = getCurrentUser();
    
    if (!$user) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Only ADMIN role can access admin routes
    if (!hasRole('ADMIN')) {
        http_response_code(403);
        echo "Access denied. Administrator access required.";
        exit;
    }

    return $user;
}

/**
 * Guard support staff route
 * Requires authentication and ADMIN or SUPPORT role
 * Redirects to login if not authenticated, shows 403 if wrong role
 * 
 * @param string $redirectUrl URL to redirect to if not authenticated
 * @return array Returns authenticated support staff user data
 */
function guardSupport(string $redirectUrl = '/login.php'): array
{
    requireAuth();
    
    $user = getCurrentUser();
    
    if (!$user) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Allow ADMIN and SUPPORT roles
    if (!hasRole(['ADMIN', 'SUPPORT'])) {
        http_response_code(403);
        echo "Access denied. Support staff access required.";
        exit;
    }

    return $user;
}

/**
 * Guard content editor route
 * Requires authentication and ADMIN or CONTENT_EDITOR role
 * Redirects to login if not authenticated, shows 403 if wrong role
 * 
 * @param string $redirectUrl URL to redirect to if not authenticated
 * @return array Returns authenticated content editor user data
 */
function guardContentEditor(string $redirectUrl = '/login.php'): array
{
    requireAuth();
    
    $user = getCurrentUser();
    
    if (!$user) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Allow ADMIN and CONTENT_EDITOR roles
    if (!hasRole(['ADMIN', 'CONTENT_EDITOR'])) {
        http_response_code(403);
        echo "Access denied. Content editor access required.";
        exit;
    }

    return $user;
}

/**
 * Get CSRF token
 * 
 * @return string The current CSRF token
 */
function getCsrfToken(): string
{
    $csrfService = new \Karyalay\Services\CsrfService();
    return $csrfService->getToken();
}

/**
 * Generate CSRF token field for forms
 * 
 * @return string HTML for hidden CSRF token input field
 */
function csrfField(): string
{
    $csrfService = new \Karyalay\Services\CsrfService();
    return $csrfService->getTokenField();
}

/**
 * Generate CSRF token meta tag for AJAX requests
 * 
 * @return string HTML for CSRF token meta tag
 */
function csrfMeta(): string
{
    $csrfService = new \Karyalay\Services\CsrfService();
    return $csrfService->getTokenMeta();
}

/**
 * Validate CSRF token from request
 * 
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken(): bool
{
    $csrfService = new \Karyalay\Services\CsrfService();
    return $csrfService->validateRequest();
}

/**
 * Require valid CSRF token or exit with 403
 * 
 * @return void Exits with 403 if invalid
 */
function requireCsrfToken(): void
{
    $csrfMiddleware = new \Karyalay\Middleware\CsrfMiddleware();
    $csrfMiddleware->requireValidToken();
}

/**
 * Sanitize string input
 * 
 * @param string|null $input The input to sanitize
 * @return string The sanitized input
 */
function sanitizeString(?string $input): string
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeString($input);
}

/**
 * Sanitize HTML input
 * 
 * @param string|null $input The HTML input to sanitize
 * @param array $allowedTags Array of allowed HTML tags
 * @return string The sanitized HTML
 */
function sanitizeHtml(?string $input, array $allowedTags = []): string
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeHtml($input, $allowedTags);
}

/**
 * Sanitize email input
 * 
 * @param string|null $input The email to sanitize
 * @return string The sanitized email
 */
function sanitizeEmail(?string $input): string
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeEmail($input);
}

/**
 * Sanitize URL input
 * 
 * @param string|null $input The URL to sanitize
 * @return string The sanitized URL
 */
function sanitizeUrl(?string $input): string
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeUrl($input);
}

/**
 * Sanitize integer input
 * 
 * @param mixed $input The input to sanitize
 * @return int The sanitized integer
 */
function sanitizeInt($input): int
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeInt($input);
}

/**
 * Sanitize float input
 * 
 * @param mixed $input The input to sanitize
 * @return float The sanitized float
 */
function sanitizeFloat($input): float
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeFloat($input);
}

/**
 * Sanitize POST data
 * 
 * @param array|null $data The POST data (defaults to $_POST)
 * @param array $types Array mapping field names to sanitization types
 * @return array The sanitized POST data
 */
function sanitizePostData(?array $data = null, array $types = []): array
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizePostData($data, $types);
}

/**
 * Sanitize GET data
 * 
 * @param array|null $data The GET data (defaults to $_GET)
 * @param array $types Array mapping field names to sanitization types
 * @return array The sanitized GET data
 */
function sanitizeGetData(?array $data = null, array $types = []): array
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->sanitizeGetData($data, $types);
}

/**
 * Escape output for safe display in HTML
 * 
 * @param string|null $output The output to escape
 * @return string The escaped output
 */
function escapeOutput(?string $output): string
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->escapeOutput($output);
}

/**
 * Escape output for safe use in JavaScript
 * 
 * @param string|null $output The output to escape
 * @return string The escaped output
 */
function escapeJs(?string $output): string
{
    $sanitizer = new \Karyalay\Services\InputSanitizationService();
    return $sanitizer->escapeJs($output);
}

