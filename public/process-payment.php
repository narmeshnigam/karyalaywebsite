<?php

/**
 * SellerPortal System
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
    header('Location: ' . get_base_url() . '/login.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . get_base_url() . '/checkout.php');
    exit;
}

use Karyalay\Models\Plan;
use Karyalay\Models\Order;
use Karyalay\Models\BillingAddress;
use Karyalay\Services\PaymentService;
use Karyalay\Services\PortAvailabilityService;

try {
    $baseUrl = get_base_url();
    
    // Validate required fields
    if (empty($_POST['plan_id']) || empty($_POST['name']) || empty($_POST['email']) || 
        empty($_POST['phone']) || empty($_POST['payment_method']) || empty($_POST['accept_terms']) ||
        empty($_POST['billing_full_name']) || empty($_POST['billing_address_line1']) || 
        empty($_POST['billing_city']) || empty($_POST['billing_state']) || 
        empty($_POST['billing_postal_code']) || empty($_POST['billing_country']) ||
        empty($_POST['billing_phone'])) {
        $_SESSION['error'] = 'Please fill in all required fields including billing address.';
        header('Location: ' . $baseUrl . '/checkout.php');
        exit;
    }
    
    // Get current user
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        $_SESSION['error'] = 'User session expired. Please log in again.';
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
    
    // Fetch plan
    $planModel = new Plan();
    $plan = $planModel->findById($_POST['plan_id']);
    
    if (!$plan || $plan['status'] !== 'ACTIVE') {
        $_SESSION['error'] = 'Invalid or inactive plan selected.';
        header('Location: ' . $baseUrl . '/pricing.php');
        exit;
    }
    
    // Check if this is a renewal (renewals don't need new ports)
    $isRenewal = isset($_POST['is_renewal']) && $_POST['is_renewal'] == '1';
    
    // Check port availability only for new subscriptions
    if (!$isRenewal) {
        $portAvailabilityService = new PortAvailabilityService();
        $availabilityCheck = $portAvailabilityService->checkAvailability($plan['id']);
        
        if (!$availabilityCheck['available']) {
            $_SESSION['error'] = 'No available ports for this plan. Please contact support.';
            header('Location: ' . $baseUrl . '/checkout.php');
            exit;
        }
    }
    
    // Calculate effective price: discounted_price if available, otherwise mrp
    $effectivePrice = !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 
        ? $plan['discounted_price'] 
        : $plan['mrp'];
    
    // Save or update billing address
    $billingAddressModel = new BillingAddress();
    $billingAddressData = [
        'full_name' => $_POST['billing_full_name'],
        'business_name' => $_POST['billing_business_name'] ?? null,
        'business_tax_id' => $_POST['billing_business_tax_id'] ?? null,
        'address_line1' => $_POST['billing_address_line1'],
        'address_line2' => $_POST['billing_address_line2'] ?? null,
        'city' => $_POST['billing_city'],
        'state' => $_POST['billing_state'],
        'postal_code' => $_POST['billing_postal_code'],
        'country' => $_POST['billing_country'],
        'phone' => $_POST['billing_phone']
    ];
    $billingAddressModel->createOrUpdate($currentUser['id'], $billingAddressData);
    
    // Create order with PENDING status and billing address
    $orderModel = new Order();
    $orderData = [
        'customer_id' => $currentUser['id'],
        'plan_id' => $plan['id'],
        'amount' => $effectivePrice,
        'currency' => $plan['currency'],
        'status' => 'PENDING',
        'payment_method' => $_POST['payment_method'],
        'billing_full_name' => $_POST['billing_full_name'],
        'billing_business_name' => $_POST['billing_business_name'] ?? null,
        'billing_business_tax_id' => $_POST['billing_business_tax_id'] ?? null,
        'billing_address_line1' => $_POST['billing_address_line1'],
        'billing_address_line2' => $_POST['billing_address_line2'] ?? null,
        'billing_city' => $_POST['billing_city'],
        'billing_state' => $_POST['billing_state'],
        'billing_postal_code' => $_POST['billing_postal_code'],
        'billing_country' => $_POST['billing_country'],
        'billing_phone' => $_POST['billing_phone']
    ];
    
    $order = $orderModel->create($orderData);
    
    if (!$order) {
        $_SESSION['error'] = 'Failed to create order. Please try again.';
        header('Location: ' . $baseUrl . '/checkout.php');
        exit;
    }
    
    // Create Razorpay payment order
    // Debug: Log payment service initialization
    $debugInfo = [];
    $debugInfo['step'] = 'initializing_payment_service';
    
    try {
        $paymentService = new PaymentService();
        $debugInfo['step'] = 'payment_service_created';
        $debugInfo['key_id_present'] = !empty($paymentService->getKeyId());
        $debugInfo['key_id_prefix'] = substr($paymentService->getKeyId(), 0, 12) . '...';
    } catch (Exception $initError) {
        $debugInfo['init_error'] = $initError->getMessage();
        error_log('PaymentService init error: ' . $initError->getMessage());
        
        // Store debug info for console output
        $_SESSION['payment_debug'] = $debugInfo;
        
        $_SESSION['error'] = 'Payment gateway configuration error. Please contact support.';
        if ($config['debug']) {
            $_SESSION['error'] .= '<br><small>Debug: ' . htmlspecialchars($initError->getMessage()) . '</small>';
        }
        header('Location: ' . $baseUrl . '/checkout.php');
        exit;
    }
    
    // Razorpay receipt max length is 40 chars - use shortened format
    // Take first 8 chars of UUID (before first hyphen) + timestamp suffix
    $shortOrderId = substr($order['id'], 0, 8);
    $receipt = 'ord_' . $shortOrderId . '_' . time();
    
    $paymentOrderData = [
        'amount' => $effectivePrice,
        'currency' => $plan['currency'],
        'receipt' => $receipt,
        'notes' => [
            'order_id' => $order['id'],
            'customer_id' => $currentUser['id'],
            'plan_id' => $plan['id'],
            'is_renewal' => $isRenewal ? '1' : '0',
            'subscription_id' => $isRenewal && !empty($_POST['subscription_id']) ? $_POST['subscription_id'] : ''
        ]
    ];
    
    $debugInfo['step'] = 'creating_payment_order';
    $debugInfo['order_data'] = [
        'amount' => $effectivePrice,
        'currency' => $plan['currency'],
        'receipt' => $receipt
    ];
    
    $paymentOrder = $paymentService->createPaymentOrder($paymentOrderData);
    $debugInfo['payment_order_result'] = $paymentOrder;
    
    // Store debug info for console output
    $_SESSION['payment_debug'] = $debugInfo;
    
    if (!$paymentOrder['success']) {
        // Update order status to FAILED
        $orderModel->updateStatus($order['id'], 'FAILED');
        
        $errorMsg = 'Failed to initiate payment.';
        if (isset($paymentOrder['error'])) {
            error_log('Payment order creation failed: ' . $paymentOrder['error']);
            if ($config['debug']) {
                $errorMsg .= '<br><small>Debug: ' . htmlspecialchars($paymentOrder['error']) . '</small>';
            }
        }
        
        $_SESSION['error'] = $errorMsg . ' Please try again.';
        header('Location: ' . $baseUrl . '/checkout.php');
        exit;
    }
    
    // Update order with payment gateway order ID
    $orderModel->update($order['id'], [
        'pg_order_id' => $paymentOrder['order_id']
    ]);
    
    // Store order ID in session for payment verification
    $_SESSION['pending_order_id'] = $order['id'];
    $_SESSION['razorpay_order_id'] = $paymentOrder['order_id'];
    
    // Store renewal information in session
    if ($isRenewal && !empty($_POST['subscription_id'])) {
        $_SESSION['is_renewal'] = true;
        $_SESSION['renewal_subscription_id'] = $_POST['subscription_id'];
    }
    
    // Prepare data for Razorpay checkout
    $razorpayKeyId = $paymentService->getKeyId();
    
} catch (Exception $e) {
    error_log('Payment processing error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Provide more specific error messages
    $errorMessage = $e->getMessage();
    
    if (strpos($errorMessage, 'Razorpay credentials not configured') !== false) {
        $_SESSION['error'] = 'Payment gateway is not configured. Please contact support.';
    } elseif (strpos($errorMessage, 'Authentication failed') !== false) {
        $_SESSION['error'] = 'Payment gateway authentication failed. Please contact support.';
    } else {
        $_SESSION['error'] = $config['debug'] 
            ? 'Payment error: ' . $errorMessage 
            : 'An error occurred while processing your payment. Please try again.';
    }
    
    if ($config['debug']) {
        $_SESSION['error'] .= '<br><br><small>Debug info: ' . $errorMessage . '</small>';
    }
    
    header('Location: ' . get_base_url() . '/checkout.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - <?php echo esc_html(get_brand_name()); ?></title>
    <link rel="stylesheet" href="<?php echo css_url('variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo css_url('reset.css'); ?>">
    <link rel="stylesheet" href="<?php echo css_url('layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo css_url('components.css'); ?>">
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
                window.location.href = '<?php echo get_base_url(); ?>/verify-payment.php?razorpay_payment_id=' + response.razorpay_payment_id + 
                    '&razorpay_order_id=' + response.razorpay_order_id + 
                    '&razorpay_signature=' + response.razorpay_signature;
            },
            "modal": {
                "ondismiss": function() {
                    // Payment cancelled by user
                    window.location.href = '<?php echo get_base_url(); ?>/payment-cancelled.php';
                }
            }
        };

        // Open Razorpay checkout
        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            // Payment failed
            window.location.href = '<?php echo get_base_url(); ?>/payment-failed.php?error=' + encodeURIComponent(response.error.description);
        });

        // Auto-open the payment modal
        rzp.open();
    </script>
</body>
</html>

