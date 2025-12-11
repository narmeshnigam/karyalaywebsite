# Email Troubleshooting Guide

## Overview
This guide helps diagnose and fix issues with payment notification emails not being sent.

## Enhanced Logging

I've added comprehensive logging throughout the email sending process. Check your error logs for these messages:

### Webhook Handler Logs
```
Webhook: Starting email notification process
Webhook: Fetching customer ID: [id]
Webhook: Fetching plan ID: [id]
Webhook: Customer found: [email]
Webhook: Plan found: [name]
Webhook: Preparing payment success email data
Webhook: Sending payment success email to: [email]
Webhook: Payment confirmation email sent successfully to customer
Webhook: Preparing admin sale notification data
Webhook: Sending new sale notification to admin
Webhook: New sale notification sent successfully to admin
```

### EmailService Logs
```
EmailService: sendPaymentSuccessEmail called
EmailService: Customer email: [email]
EmailService: Calling send method for payment success email
EmailService: Attempting to send email to: [email]
EmailService: Subject: [subject]
EmailService: Sending email via SMTP...
EmailService: Email sent successfully to: [email]
```

## Common Issues and Solutions

### 1. SMTP Not Configured

**Symptoms:**
- Logs show: "Email sending error: SMTP connect() failed"
- Emails not being sent at all

**Solution:**
1. Go to Admin → Settings → Email (SMTP Settings)
2. Configure SMTP settings:
   - SMTP Host (e.g., smtp.gmail.com)
   - SMTP Port (usually 587 for TLS, 465 for SSL)
   - SMTP Username (your email)
   - SMTP Password (app password if using Gmail)
   - SMTP Encryption (TLS or SSL)
   - From Address
   - From Name
3. Test the connection using the test email feature

### 2. Customer/Plan Not Found

**Symptoms:**
- Logs show: "Customer not found for ID: [id]"
- Logs show: "Plan not found for ID: [id]"

**Solution:**
- Verify the order has valid customer_id and plan_id
- Check database for customer and plan records
- Ensure foreign key relationships are correct

### 3. Webhook Not Being Called

**Symptoms:**
- No webhook logs appearing at all
- Payment succeeds but no subscription created

**Solution:**
1. Check payment gateway webhook configuration
2. Verify webhook URL is correct: `https://yourdomain.com/webhook-payment.php`
3. Ensure webhook signature verification is working
4. Check payment gateway dashboard for webhook delivery status

### 4. Email Method Not Being Called

**Symptoms:**
- Webhook logs show subscription created
- But no email-related logs appear

**Solution:**
- Check if the email sending code is inside the function
- Verify no early returns before email code
- Check for exceptions being caught silently

### 5. SMTP Authentication Failed

**Symptoms:**
- Logs show: "SMTP Error: Could not authenticate"
- Logs show: "Invalid login"

**Solution:**
1. **For Gmail:**
   - Enable 2-factor authentication
   - Generate an App Password
   - Use the App Password in SMTP settings
   
2. **For other providers:**
   - Verify username and password are correct
   - Check if "less secure apps" needs to be enabled
   - Verify SMTP host and port are correct

### 6. Notifications Email Not Set

**Symptoms:**
- Customer email sent successfully
- Admin notification not sent
- Logs show: "Using fallback email: admin@karyalay.com"

**Solution:**
1. Go to Admin → Settings → General
2. Set "Notifications Email" field
3. Save settings
4. Test with a new purchase

## Checking Error Logs

### Location of Error Logs

**Apache:**
```bash
tail -f /var/log/apache2/error.log
```

**Nginx:**
```bash
tail -f /var/log/nginx/error.log
```

**PHP-FPM:**
```bash
tail -f /var/log/php-fpm/error.log
```

**Custom PHP error log:**
```bash
tail -f /path/to/your/php_errors.log
```

### Filtering Webhook Logs

```bash
# Show only webhook-related logs
tail -f /var/log/apache2/error.log | grep "Webhook:"

# Show only email-related logs
tail -f /var/log/apache2/error.log | grep "EmailService:"
```

## Testing Email Functionality

### 1. Test SMTP Configuration

Create a test script: `test-email.php`

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Karyalay\Services\EmailService;

$emailService = new EmailService();

$result = $emailService->send(
    'your-test-email@example.com',
    'Test Email',
    '<h1>Test Email</h1><p>If you receive this, SMTP is working!</p>',
    'Test Email - If you receive this, SMTP is working!'
);

if ($result) {
    echo "Email sent successfully!\n";
} else {
    echo "Email failed to send. Check error logs.\n";
}
```

Run it:
```bash
php test-email.php
```

### 2. Test Payment Email Methods

Create: `test-payment-email.php`

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Karyalay\Services\EmailService;

$emailService = new EmailService();

// Test customer payment email
$paymentData = [
    'customer_name' => 'Test Customer',
    'customer_email' => 'your-test-email@example.com',
    'plan_name' => 'Test Plan',
    'amount' => '99.00',
    'currency' => 'USD',
    'order_id' => 'TEST1234',
    'payment_id' => 'pay_test123',
    'invoice_url' => 'http://localhost/app/billing/invoice.php?id=test'
];

echo "Sending payment success email...\n";
$result1 = $emailService->sendPaymentSuccessEmail($paymentData);
echo $result1 ? "✓ Customer email sent\n" : "✗ Customer email failed\n";

// Test admin sale notification
$saleData = [
    'customer_name' => 'Test Customer',
    'customer_email' => 'customer@example.com',
    'customer_phone' => '+1234567890',
    'plan_name' => 'Test Plan',
    'plan_price' => '99.00',
    'currency' => 'USD',
    'order_id' => 'TEST1234',
    'subscription_id' => 'SUB1234',
    'payment_id' => 'pay_test123',
    'payment_method' => 'Online Payment'
];

echo "Sending admin sale notification...\n";
$result2 = $emailService->sendNewSaleNotification($saleData);
echo $result2 ? "✓ Admin email sent\n" : "✗ Admin email failed\n";
```

Run it:
```bash
php test-payment-email.php
```

### 3. Simulate Webhook Call

Create: `test-webhook.php`

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Karyalay\Models\Order;
use Karyalay\Models\User;
use Karyalay\Models\Plan;

// Find a test order
$orderModel = new Order();
$orders = $orderModel->getAll(['status' => 'PENDING'], 1, 0);

if (empty($orders)) {
    echo "No pending orders found. Create a test order first.\n";
    exit;
}

$order = $orders[0];

echo "Testing with order: {$order['id']}\n";
echo "Customer ID: {$order['customer_id']}\n";
echo "Plan ID: {$order['plan_id']}\n";

// Simulate the email sending part
require_once __DIR__ . '/public/webhook-payment.php';

// This would normally be called by the webhook
// handleNewSubscriptionPayment($order);

echo "\nCheck error logs for detailed output.\n";
```

## Debugging Checklist

- [ ] SMTP settings configured in Admin → Settings → Email
- [ ] Test email sends successfully
- [ ] Notifications email set in Admin → Settings → General
- [ ] Webhook URL configured in payment gateway
- [ ] Webhook signature verification working
- [ ] Customer and plan records exist in database
- [ ] Error logs show webhook being called
- [ ] Error logs show email methods being called
- [ ] Error logs show SMTP connection successful
- [ ] Check spam/junk folders for test emails

## Quick Fixes

### Fix 1: Enable Debug Mode

In `config/app.php`:
```php
'debug' => true,
```

This will show more detailed error messages.

### Fix 2: Disable SMTP Temporarily

For testing, you can temporarily use PHP's mail() function by modifying EmailService:

```php
// In send() method, add before $this->mailer->send():
if (getenv('USE_PHP_MAIL') === 'true') {
    return mail($to, $subject, $body);
}
```

Then set environment variable:
```bash
export USE_PHP_MAIL=true
```

### Fix 3: Check PHPMailer Version

```bash
composer show phpmailer/phpmailer
```

Ensure you have a recent version (6.x or higher).

### Fix 4: Verify Autoloader

```bash
composer dump-autoload
```

## Getting Help

If emails still aren't sending after following this guide:

1. **Collect Information:**
   - Error log excerpts (last 50 lines)
   - SMTP settings (without password)
   - PHP version: `php -v`
   - PHPMailer version: `composer show phpmailer/phpmailer`

2. **Check These:**
   - Firewall blocking SMTP ports (587, 465)
   - Server IP blacklisted by email provider
   - DNS records (SPF, DKIM) if using custom domain
   - Email provider rate limits

3. **Test Alternatives:**
   - Try different SMTP provider (Gmail, SendGrid, Mailgun)
   - Test from command line: `telnet smtp.gmail.com 587`
   - Use online SMTP testing tools

## Success Indicators

When everything is working correctly, you should see:

1. **In Error Logs:**
```
Webhook: Starting email notification process
Webhook: Customer found: customer@example.com
Webhook: Plan found: Professional Plan
EmailService: sendPaymentSuccessEmail called
EmailService: Email sent successfully to: customer@example.com
EmailService: sendNewSaleNotification called
EmailService: Email sent successfully to: admin@example.com
```

2. **In Email Inbox:**
- Customer receives payment confirmation with invoice link
- Admin receives sale notification with all details

3. **In Database:**
- Order status is SUCCESS
- Subscription is created and active
- Port allocated (if available)
