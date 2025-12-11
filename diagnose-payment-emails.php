<?php
/**
 * Comprehensive Payment Email Diagnostics
 * 
 * This script performs a complete diagnostic of the payment email workflow
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/app.php';

use Karyalay\Services\EmailService;
use Karyalay\Models\Setting;

echo "=== Payment Email Diagnostics ===\n\n";

// Test 1: Check SMTP Configuration
echo "Test 1: Checking SMTP Configuration...\n";
try {
    $settingModel = new Setting();
    $smtpSettings = [
        'smtp_host' => $settingModel->get('smtp_host'),
        'smtp_port' => $settingModel->get('smtp_port'),
        'smtp_username' => $settingModel->get('smtp_username'),
        'smtp_password' => $settingModel->get('smtp_password') ? '***SET***' : 'NOT SET',
        'smtp_encryption' => $settingModel->get('smtp_encryption'),
        'smtp_from_address' => $settingModel->get('smtp_from_address'),
        'smtp_from_name' => $settingModel->get('smtp_from_name'),
    ];
    
    echo "  SMTP Settings from Database:\n";
    foreach ($smtpSettings as $key => $value) {
        $status = !empty($value) && $value !== 'NOT SET' ? '✓' : '✗';
        echo "    {$status} {$key}: {$value}\n";
    }
    
    $allSet = true;
    foreach ($smtpSettings as $key => $value) {
        if (empty($value) || $value === 'NOT SET') {
            $allSet = false;
            break;
        }
    }
    
    if ($allSet) {
        echo "  ✓ All SMTP settings are configured\n\n";
    } else {
        echo "  ✗ Some SMTP settings are missing\n\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Check notification email
echo "Test 2: Checking Notification Email Configuration...\n";
try {
    $settingModel = new Setting();
    $notificationsEmail = $settingModel->get('notifications_email');
    $contactEmail = $settingModel->get('contact_email');
    $envAdminEmail = $_ENV['ADMIN_EMAIL'] ?? 'NOT SET';
    
    echo "  Notification email sources:\n";
    echo "    notifications_email (DB): " . ($notificationsEmail ?: 'NOT SET') . "\n";
    echo "    contact_email (DB): " . ($contactEmail ?: 'NOT SET') . "\n";
    echo "    ADMIN_EMAIL (ENV): {$envAdminEmail}\n";
    
    $finalEmail = $notificationsEmail ?: ($contactEmail ?: $envAdminEmail);
    echo "  ✓ Final admin email: {$finalEmail}\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Check recent orders
echo "Test 3: Checking Recent Orders...\n";
try {
    $db = \Karyalay\Database\Connection::getInstance();
    
    // Count orders by status
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Order counts by status:\n";
    foreach ($statusCounts as $row) {
        echo "    {$row['status']}: {$row['count']}\n";
    }
    
    // Get most recent successful order
    $stmt = $db->prepare("
        SELECT id, customer_id, plan_id, amount, currency, created_at 
        FROM orders 
        WHERE status = 'SUCCESS' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $recentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($recentOrder) {
        echo "\n  Most recent successful order:\n";
        echo "    ID: " . substr($recentOrder['id'], 0, 8) . "...\n";
        echo "    Amount: {$recentOrder['amount']} {$recentOrder['currency']}\n";
        echo "    Created: {$recentOrder['created_at']}\n";
    } else {
        echo "\n  ✗ No successful orders found\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Check webhook logs
echo "Test 4: Checking for Webhook Logs...\n";
$logLocations = [
    '/var/log/php_errors.log',
    '/var/log/apache2/error.log',
    '/Applications/XAMPP/xamppfiles/logs/php_error_log',
    '/Applications/XAMPP/xamppfiles/logs/error_log',
    __DIR__ . '/storage/logs/app.log',
    __DIR__ . '/storage/logs/webhook.log',
];

$foundLogs = [];
foreach ($logLocations as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        $foundLogs[] = $logPath;
    }
}

if (!empty($foundLogs)) {
    echo "  Found log files:\n";
    foreach ($foundLogs as $logPath) {
        echo "    ✓ {$logPath}\n";
    }
    echo "\n  To check webhook logs, run:\n";
    echo "    tail -f " . $foundLogs[0] . "\n\n";
} else {
    echo "  ✗ No log files found in common locations\n";
    echo "  Check your PHP error_log configuration\n\n";
}

// Test 5: Test EmailService instantiation
echo "Test 5: Testing EmailService Instantiation...\n";
try {
    $emailService = new EmailService();
    echo "  ✓ EmailService created successfully\n";
    
    // Check if methods exist
    $methods = ['send', 'sendEmail', 'sendPaymentSuccessEmail', 'sendNewSaleNotification'];
    $allMethodsExist = true;
    
    foreach ($methods as $method) {
        if (method_exists($emailService, $method)) {
            echo "  ✓ Method '{$method}' exists\n";
        } else {
            echo "  ✗ Method '{$method}' is MISSING\n";
            $allMethodsExist = false;
        }
    }
    
    if ($allMethodsExist) {
        echo "  ✓ All required methods exist\n\n";
    } else {
        echo "  ✗ Some methods are missing\n\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Error creating EmailService: " . $e->getMessage() . "\n\n";
}

// Test 6: Check webhook file
echo "Test 6: Checking Webhook File...\n";
$webhookPath = __DIR__ . '/public/webhook-payment.php';
if (file_exists($webhookPath)) {
    echo "  ✓ Webhook file exists: {$webhookPath}\n";
    
    $webhookContent = file_get_contents($webhookPath);
    
    // Check for email-related code
    $checks = [
        'use Karyalay\Services\EmailService' => 'EmailService import',
        'sendPaymentSuccessEmail' => 'Customer email method call',
        'sendNewSaleNotification' => 'Admin email method call',
        'error_log' => 'Error logging',
    ];
    
    foreach ($checks as $search => $description) {
        if (strpos($webhookContent, $search) !== false) {
            echo "  ✓ Found: {$description}\n";
        } else {
            echo "  ✗ Missing: {$description}\n";
        }
    }
    echo "\n";
} else {
    echo "  ✗ Webhook file not found\n\n";
}

// Summary and recommendations
echo "=== Summary and Recommendations ===\n\n";

echo "Based on the diagnostics above:\n\n";

echo "1. SMTP Configuration:\n";
echo "   - Verify all SMTP settings are correct in the database\n";
echo "   - Test SMTP connection with a simple email\n\n";

echo "2. Webhook Testing:\n";
echo "   - Make a test purchase\n";
echo "   - Monitor the webhook logs for email-related messages\n";
echo "   - Look for 'Webhook: Sending payment success email' in logs\n\n";

echo "3. Email Delivery:\n";
echo "   - Check spam folders\n";
echo "   - Verify sender email is not blacklisted\n";
echo "   - Check SMTP provider's sending limits\n\n";

echo "4. Debugging Commands:\n";
echo "   - Watch logs: tail -f /path/to/php_errors.log | grep -i 'webhook\\|email'\n";
echo "   - Test SMTP: php test-smtp-connection.php\n";
echo "   - Simulate webhook: php simulate-payment-webhook-emails.php\n\n";

echo "5. Common Issues:\n";
echo "   - Webhook URL not configured in payment gateway\n";
echo "   - Webhook signature verification failing\n";
echo "   - SMTP authentication failing\n";
echo "   - Emails going to spam\n";
echo "   - Daily sending limit reached\n\n";

echo "Next Steps:\n";
echo "1. Run: php test-smtp-connection.php (create this to test SMTP)\n";
echo "2. Make a test purchase\n";
echo "3. Check logs immediately after purchase\n";
echo "4. Look for error messages in webhook logs\n\n";
