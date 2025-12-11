<?php
/**
 * Payment Gateway Test API
 * Tests Razorpay connection and optionally processes a test payment of INR 1.00
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

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
    
    // Get Razorpay configuration from POST data
    $keyId = trim($_POST['razorpay_key_id'] ?? '');
    $keySecret = trim($_POST['razorpay_key_secret'] ?? '');
    $action = $_POST['action'] ?? 'verify';
    
    // Validate required fields
    if (empty($keyId)) {
        throw new Exception('Razorpay Key ID is required.');
    }
    
    if (empty($keySecret)) {
        throw new Exception('Razorpay Key Secret is required.');
    }
    
    // Validate key format
    if (!preg_match('/^rzp_(test|live)_[a-zA-Z0-9]+$/', $keyId)) {
        throw new Exception('Invalid Razorpay Key ID format. It should start with rzp_test_ or rzp_live_');
    }
    
    // Create Razorpay API instance
    $razorpay = new Api($keyId, $keySecret);
    
    switch ($action) {
        case 'verify':
            // Test connection by fetching orders (empty list is fine)
            $orders = $razorpay->order->all(['count' => 1]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Razorpay credentials verified successfully! Connection to payment gateway is working.',
                'can_test_payment' => true,
                'mode' => strpos($keyId, 'rzp_test_') === 0 ? 'test' : 'live'
            ]);
            break;
            
        case 'create_test_order':
            // Create a test order for INR 1.00
            $order = $razorpay->order->create([
                'amount' => 100, // Amount in paise (100 paise = INR 1.00)
                'currency' => 'INR',
                'receipt' => 'test_' . time() . '_' . bin2hex(random_bytes(4)),
                'notes' => [
                    'purpose' => 'Payment Gateway Test',
                    'admin_test' => 'true',
                    'created_by' => getCurrentUser()['email'] ?? 'admin'
                ]
            ]);
            
            // Log the test order creation
            $currentUser = getCurrentUser();
            if ($currentUser) {
                error_log('Test payment order created by admin: ' . $currentUser['email'] . ' - Order ID: ' . $order['id']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Test order created successfully.',
                'order_id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'key_id' => $keyId
            ]);
            break;
            
        case 'verify_payment':
            // Verify a completed test payment
            $orderId = $_POST['razorpay_order_id'] ?? '';
            $paymentId = $_POST['razorpay_payment_id'] ?? '';
            $signature = $_POST['razorpay_signature'] ?? '';
            
            if (empty($orderId) || empty($paymentId) || empty($signature)) {
                throw new Exception('Missing payment verification parameters.');
            }
            
            // Verify signature
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ];
            
            try {
                $razorpay->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                throw new Exception('Payment signature verification failed. The payment may be fraudulent.');
            }
            
            // Fetch payment details to confirm
            $payment = $razorpay->payment->fetch($paymentId);
            
            // Log successful test payment
            $currentUser = getCurrentUser();
            if ($currentUser) {
                error_log('Test payment verified by admin: ' . $currentUser['email'] . 
                         ' - Payment ID: ' . $paymentId . 
                         ' - Amount: ' . ($payment['amount'] / 100) . ' ' . $payment['currency']);
            }
            
            // Initiate automatic refund for test payment
            $refundResult = null;
            try {
                $refund = $razorpay->refund->create([
                    'payment_id' => $paymentId,
                    'amount' => $payment['amount'],
                    'notes' => [
                        'reason' => 'Test payment auto-refund',
                        'admin_test' => 'true'
                    ]
                ]);
                $refundResult = [
                    'refund_id' => $refund['id'],
                    'status' => $refund['status'],
                    'amount' => $refund['amount'] / 100
                ];
                error_log('Test payment auto-refunded: ' . $refund['id']);
            } catch (Exception $refundError) {
                error_log('Auto-refund failed for test payment: ' . $refundError->getMessage());
                $refundResult = [
                    'error' => 'Auto-refund failed: ' . $refundError->getMessage()
                ];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment verified successfully! Your payment gateway is fully configured and working.',
                'payment' => [
                    'id' => $payment['id'],
                    'amount' => $payment['amount'] / 100,
                    'currency' => $payment['currency'],
                    'status' => $payment['status'],
                    'method' => $payment['method'] ?? 'N/A'
                ],
                'refund' => $refundResult
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified.');
    }
    
} catch (Exception $e) {
    error_log('Payment test error: ' . $e->getMessage());
    
    // Provide more helpful error messages
    $errorMessage = $e->getMessage();
    
    // Check for common Razorpay errors
    if (strpos($errorMessage, 'Authentication failed') !== false || 
        strpos($errorMessage, '401') !== false) {
        $errorMessage = 'Authentication failed. Please check your Razorpay Key ID and Key Secret.';
    } elseif (strpos($errorMessage, 'curl') !== false || 
              strpos($errorMessage, 'Could not resolve') !== false) {
        $errorMessage = 'Network error. Unable to connect to Razorpay servers. Please check your internet connection.';
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
}
