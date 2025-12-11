<?php
/**
 * Send OTP API Endpoint
 * 
 * Generates and sends an OTP to the provided email address
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../../config/app.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

use Karyalay\Services\OtpService;
use Karyalay\Services\EmailService;
use Karyalay\Models\User;

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    
    // Validate email
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Check purpose - 'credentials' allows existing users, 'registration' blocks them
    $purpose = $input['purpose'] ?? 'registration';
    
    $userModel = new User();
    $existingUser = $userModel->findByEmail($email);
    
    if ($purpose === 'registration' && $existingUser) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }
    
    if ($purpose === 'credentials' && !$existingUser) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    if ($purpose === 'password_reset' && !$existingUser) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No account found with this email']);
        exit;
    }
    
    // Generate OTP
    $otpService = new OtpService();
    $result = $otpService->generateOtp($email);
    
    if (!$result['success']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'cooldown' => $result['cooldown'] ?? null
        ]);
        exit;
    }
    
    // Send OTP email
    $emailService = new EmailService();
    $emailSent = $emailService->sendOtpEmail($email, $result['otp']);
    
    if (!$emailSent) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to send verification email. Please try again.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification code sent to your email',
        'expires_in' => $result['expires_in']
    ]);
    
} catch (Exception $e) {
    error_log('Send OTP error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
