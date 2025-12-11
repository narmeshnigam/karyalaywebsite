<?php
/**
 * Verify OTP API Endpoint
 * 
 * Verifies the OTP code provided by the user
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

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $otp = trim($input['otp'] ?? '');
    
    // Validate inputs
    if (empty($email) || empty($otp)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email and OTP are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    if (!preg_match('/^\d{6}$/', $otp)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid OTP format']);
        exit;
    }
    
    // Verify OTP
    $otpService = new OtpService();
    $result = $otpService->verifyOtp($email, $otp);
    
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Verify OTP error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
