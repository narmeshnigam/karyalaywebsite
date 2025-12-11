<?php
/**
 * SMTP Connection Test API
 * Tests SMTP connection and optionally sends a test email
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set JSON header
header('Content-Type: application/json');

// Start session and check admin authentication
startSecureSession();

// Check if user is authenticated and is admin
if (!isAuthenticated() || !isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid security token.');
    }
    
    // Get SMTP configuration from POST data
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpPort = trim($_POST['smtp_port'] ?? '');
    $smtpUsername = trim($_POST['smtp_username'] ?? '');
    $smtpPassword = $_POST['smtp_password'] ?? '';
    $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
    $smtpFromAddress = trim($_POST['smtp_from_address'] ?? '');
    $smtpFromName = trim($_POST['smtp_from_name'] ?? '');
    $sendTestEmail = isset($_POST['send_test_email']) && $_POST['send_test_email'] === 'true';
    $testEmailAddress = trim($_POST['test_email_address'] ?? '');
    
    // Validate required fields
    if (empty($smtpHost)) {
        throw new Exception('SMTP Host is required.');
    }
    
    if (empty($smtpPort) || !is_numeric($smtpPort)) {
        throw new Exception('Valid SMTP Port is required.');
    }
    
    if (empty($smtpUsername)) {
        throw new Exception('SMTP Username is required.');
    }
    
    if (empty($smtpPassword)) {
        throw new Exception('SMTP Password is required.');
    }
    
    // If sending test email, validate email address
    if ($sendTestEmail) {
        if (empty($testEmailAddress)) {
            throw new Exception('Test email address is required.');
        }
        
        if (!filter_var($testEmailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid test email address.');
        }
    }
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->Port = (int)$smtpPort;
    
    // Set encryption
    if ($smtpEncryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpEncryption === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    
    // Set timeout
    $mail->Timeout = 10;
    $mail->SMTPDebug = 0; // Disable debug output
    
    // Test connection
    if (!$mail->smtpConnect()) {
        throw new Exception('Failed to connect to SMTP server. Please check your settings.');
    }
    
    // If we're just testing connection, close and return success
    if (!$sendTestEmail) {
        $mail->smtpClose();
        
        echo json_encode([
            'success' => true,
            'message' => 'SMTP connection successful! You can now send a test email to verify email delivery.',
            'can_send_test' => true
        ]);
        exit;
    }
    
    // Send test email
    $mail->setFrom($smtpFromAddress ?: $smtpUsername, $smtpFromName ?: 'Test Email');
    $mail->addAddress($testEmailAddress);
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Email - ' . date('Y-m-d H:i:s');
    
    // Email body
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            .success-icon { font-size: 48px; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="success-icon">✅</div>
                <h1 style="margin: 0;">SMTP Test Successful!</h1>
            </div>
            <div class="content">
                <p>Congratulations! Your SMTP configuration is working correctly.</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0;">Connection Details:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li><strong>SMTP Host:</strong> ' . htmlspecialchars($smtpHost) . '</li>
                        <li><strong>SMTP Port:</strong> ' . htmlspecialchars($smtpPort) . '</li>
                        <li><strong>Encryption:</strong> ' . strtoupper(htmlspecialchars($smtpEncryption)) . '</li>
                        <li><strong>From Address:</strong> ' . htmlspecialchars($smtpFromAddress ?: $smtpUsername) . '</li>
                        <li><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</li>
                    </ul>
                </div>
                
                <p>This test email confirms that:</p>
                <ul>
                    <li>✓ SMTP server connection is successful</li>
                    <li>✓ Authentication credentials are correct</li>
                    <li>✓ Email delivery is working properly</li>
                </ul>
                
                <p>You can now use this configuration to send emails from your application.</p>
                
                <div class="footer">
                    <p>This is an automated test email. Please do not reply.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ';
    
    $mail->AltBody = 'SMTP Test Email - Your SMTP configuration is working correctly! Test time: ' . date('Y-m-d H:i:s');
    
    // Send the email
    if (!$mail->send()) {
        throw new Exception('Failed to send test email: ' . $mail->ErrorInfo);
    }
    
    // Log the test
    $currentUser = getCurrentUser();
    if ($currentUser) {
        error_log('SMTP test email sent by admin: ' . $currentUser['email'] . ' to: ' . $testEmailAddress);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully to ' . htmlspecialchars($testEmailAddress) . '! Please check your inbox.'
    ]);
    
} catch (Exception $e) {
    error_log('SMTP test error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
