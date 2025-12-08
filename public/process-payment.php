<?php

/**
 * Karyalay Portal System
 * Process Payment - Initiate Payment
 * 
 * Creates order and initiates payment with Razorpay
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Include template helpers for getCurrentUser function
require_once __DIR__ . '/../includes/template_helpers.php';

// Start secure session
startSecureSession();

// Check if user is logged in
if (!isAuthenticated()) {
    $_SESSION['error'] = 'Please log in to complete payment.';
    header('Location: /karyalayportal/login.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /karyalayportal/checkout.php');
    exit;
}

use Karyalay\Models\Plan;
use Karyalay\Models\Order;
use Karyalay\Services\PaymentService;
use Karyalay\Services\PortAvailabilityService;

try {
    // Validate required fields
    if (empty($_POST['plan_id']) || empty($_POST['name']) || empty($_POST['email']) || 
        empty($_POST['phone']) || empty($_POST['payment_method']) || empty($_POST['accept_terms'])) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: /karyalayportal/checkout.php');
        exit;
    }
    
    // Get current user
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        $_SESSION['error'] = 'User session expired. Please log in again.';
        header('Location: /karyalayportal/login.php');
        exit;
    }
    
    // Fetch plan
    $planModel = new Plan();
    $plan = $planModel->findById($_POST['plan_id']);
    
    if (!$plan || $plan['status'] !== 'ACTIVE') {
        $_SESSION['error'] = 'Invalid or inactive plan selected.';
        header('Location: /karyalayportal/pricing.php');
        exit;
    }
    
    // Check port availability
    $portAvailabilityService = new PortAvailabilityService();
    $availabilityCheck = $portAvailabilityService->checkAvailability($plan['id']);
    
    if (!$availabilityCheck['available']) {
        $_SESSION['error'] = 'No available ports for this plan. Please contact support.';
        header('Location: /karyalayportal/checkout.php');
        exit;
    }
    
    // Create order with PENDING status
    $orderModel = new Order();
    $orderData = [
        'customer_id' => $currentUser['id'],
        'plan_id' => $plan['id'],
        'amount' => $plan['price'],
        'currency' => $plan['currency'],
        'status' => 'PENDING',
        'payment_method' => $_POST['payment_method']
    ];
    
    $order = $orderModel->create($orderData);
    
    if (!$order) {
        $_SESSION['error'] = 'Failed to create order. Please try again.';
        header('Location: /karyalayportal/checkout.php');
        exit;
    }
    
    // Create Razorpay payment order
    $paymentService = new PaymentService();
    $paymentOrderData = [
        'amount' => $plan['price'],
        'currency' => $plan['currency'],
        'receipt' => 'order_' . $order['id'],
        'notes' => [
            'order_id' => $order['id'],
            'customer_id' => $currentUser['id'],
            'plan_id' => $plan['id']
        ]
    ];
    
    $paymentOrder = $paymentService->createPaymentOrder($paymentOrderData);
    
    if (!$paymentOrder['success']) {
        // Update order status to FAILED
        $orderModel->updateStatus($order['id'], 'FAILED');
        
        $_SESSION['error'] = 'Failed to initiate payment. Please try again.';
        header('Location: /karyalayportal/checkout.php');
        exit;
    }
    
    // Update order with payment gateway ID
    $orderModel->update($order['id'], [
        'payment_gateway_id' => $paymentOrder['order_id']
    ]);
    
    // Store order ID in session for payment verification
    $_SESSION['pending_order_id'] = $order['id'];
    $_SESSION['razorpay_order_id'] = $paymentOrder['order_id'];
    
    // Prepare data for Razorpay checkout
    $razorpayKeyId = $paymentService->getKeyId();
    
} catch (Exception $e) {
    error_log('Payment processing error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    if ($config['debug']) {
        die('Payment processing error: ' . $e->getMessage() . '<br><br>Stack trace:<br>' . nl2br($e->getTraceAsString()));
    }
    
    $_SESSION['error'] = 'An error occurred while processing your payment. Please try again.';
    header('Location: /karyalayportal/checkout.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Karyalay Portal</title>
    <link rel="stylesheet" href="/karyalayportal/assets/css/variables.css">
    <link rel="stylesheet" href="/karyalayportal/assets/css/reset.css">
    <link rel="stylesheet" href="/karyalayportal/assets/css/layout.css">
    <link rel="stylesheet" href="/karyalayportal/assets/css/components.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="container" style="padding: 2rem; text-align: center;">
        <h1>Processing Payment...</h1>
        <p>Please wait while we redirect you to the payment gateway.</p>
    </div>

    <script>
        // Razorpay checkout options
        var options = {
            "key": "<?php echo htmlspecialchars($razorpayKeyId); ?>",
            "amount": "<?php echo $paymentOrder['amount']; ?>",
            "currency": "<?php echo htmlspecialchars($paymentOrder['currency']); ?>",
            "name": "<?php echo htmlspecialchars($config['name']); ?>",
            "description": "<?php echo htmlspecialchars($plan['name']); ?>",
            "order_id": "<?php echo htmlspecialchars($paymentOrder['order_id']); ?>",
            "prefill": {
                "name": "<?php echo htmlspecialchars($_POST['name']); ?>",
                "email": "<?php echo htmlspecialchars($_POST['email']); ?>",
                "contact": "<?php echo htmlspecialchars($_POST['phone']); ?>"
            },
            "theme": {
                "color": "#3399cc"
            },
            "handler": function (response) {
                // Payment successful - redirect to verification
                window.location.href = '/karyalayportal/verify-payment.php?razorpay_payment_id=' + response.razorpay_payment_id + 
                    '&razorpay_order_id=' + response.razorpay_order_id + 
                    '&razorpay_signature=' + response.razorpay_signature;
            },
            "modal": {
                "ondismiss": function() {
                    // Payment cancelled by user
                    window.location.href = '/karyalayportal/payment-cancelled.php';
                }
            }
        };

        // Open Razorpay checkout
        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            // Payment failed
            window.location.href = '/karyalayportal/payment-failed.php?error=' + encodeURIComponent(response.error.description);
        });

        // Auto-open the payment modal
        rzp.open();
    </script>
</body>
</html>

