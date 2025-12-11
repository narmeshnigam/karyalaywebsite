# Instance Provisioned Email Implementation

## Overview

A new email has been created to notify customers when their instance is provisioned and ready to use. This email is sent **separately** from the payment confirmation email and includes:

- ✅ Instance URL
- ✅ Setup instructions
- ✅ Link to "My Port" page to view credentials
- ✅ Step-by-step getting started guide
- ❌ Does NOT include passwords or database credentials (for security)

## Email Handler

**File:** `classes/Services/EmailServices/InstanceProvisionedEmail.php`

### Features

1. **Beautiful HTML Template**
   - Gradient header with celebration emoji
   - Prominent instance URL display
   - Color-coded information boxes
   - Step-by-step setup instructions
   - Call-to-action button to view credentials

2. **Security-Focused**
   - Does NOT include sensitive credentials in email
   - Directs users to secure dashboard to view credentials
   - Includes security best practices

3. **User-Friendly**
   - Clear, numbered setup steps
   - Links to relevant pages
   - Help section for support
   - Plain text alternative for email clients

## Integration

### EmailService Facade

Added new method to `classes/Services/EmailService.php`:

```php
public function sendInstanceProvisionedEmail(array $instanceData): bool
```

### Required Data

```php
$instanceData = [
    'customer_name' => 'Customer Name',
    'customer_email' => 'customer@email.com',
    'plan_name' => 'Plan Name',
    'instance_url' => 'http://instance-url.com',
    'my_port_url' => 'http://yoursite.com/app/my-port.php'
];
```

## Trigger Points

### 1. Frontend Payment Verification

**File:** `public/verify-payment.php`

Email is sent after successful payment when a port is allocated:

```php
// Send instance provisioned email if port was allocated
if ($result['port_allocated'] && isset($result['port']['instance_url'])) {
    $instanceData = [
        'customer_name' => $customer['name'],
        'customer_email' => $customer['email'],
        'plan_name' => $plan['name'],
        'instance_url' => $result['port']['instance_url'],
        'my_port_url' => $baseUrl . '/app/my-port.php'
    ];
    
    $emailService->sendInstanceProvisionedEmail($instanceData);
}
```

### 2. Webhook Handler

**File:** `public/webhook-payment.php`

Same logic applied to webhook for when Razorpay webhook is configured.

## Email Flow

When a customer completes a payment:

1. **Payment Success Email** (Immediate)
   - Confirms payment received
   - Shows amount paid
   - Provides invoice link

2. **New Sale Notification** (Immediate - to Admin)
   - Notifies admin of new sale
   - Includes customer and payment details

3. **Instance Provisioned Email** (When port allocated)
   - Sent only if instance is successfully provisioned
   - Includes instance URL and setup instructions
   - Directs to My Port page for credentials

## Email Content

### Subject Line
```
Your Instance is Ready - [Site Name]
```

### Key Sections

1. **Header**
   - Celebration message
   - Plan name

2. **Instance URL Box**
   - Prominently displayed
   - Clickable link
   - Easy to copy

3. **Getting Started**
   - Quick overview
   - What to do next

4. **Credentials Access**
   - Explains where to find credentials
   - Link to My Port page
   - Security notice

5. **Setup Instructions**
   - Step 1: Access Your Instance
   - Step 2: Get Your Credentials
   - Step 3: Log In
   - Step 4: Start Building

6. **Help Section**
   - Support information
   - Encouragement

## Testing

### Manual Test

```bash
php test-instance-provisioned-email.php
```

This will:
1. Find the most recent order with port allocation
2. Fetch customer and plan details
3. Prepare instance data
4. Send the email
5. Confirm delivery

### Test Payment Flow

1. Complete a test purchase
2. Check logs for:
   ```
   Verify Payment: Sending instance provisioned email
   Verify Payment: Instance provisioned email sent successfully
   ```
3. Check customer inbox (and spam folder)

### Expected Logs

```
Verify Payment: Payment processed successfully for order [id]
Verify Payment: Sending confirmation emails
Verify Payment: Payment confirmation email sent successfully to customer
Verify Payment: New sale notification sent successfully to admin
Verify Payment: Sending instance provisioned email
Verify Payment: Instance provisioned email sent successfully
Verify Payment: Port allocated - [instance_url]
```

## Monitoring

### Check Email Sending

```bash
# Watch for instance provisioned emails
tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log | grep "instance provisioned"

# Watch all email activity
tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log | grep -i "email"
```

### Verify Email Delivery

1. Check customer inbox
2. Check spam/junk folder
3. Verify email contains:
   - Instance URL
   - Link to My Port page
   - Setup instructions
   - No sensitive credentials

## Security Considerations

### What's Included ✅
- Instance URL (public-facing)
- Link to My Port page
- General setup instructions
- Customer name and plan name

### What's NOT Included ❌
- Database passwords
- Admin credentials
- API keys
- SSH keys
- Any sensitive configuration

### Why?
- Email is not secure (can be intercepted)
- Credentials should only be viewed in secure dashboard
- Follows security best practices
- Reduces risk of credential exposure

## Customization

### Modify Template

Edit `classes/Services/EmailServices/InstanceProvisionedEmail.php`:

- `renderTemplate()` - HTML version
- `renderPlainText()` - Plain text version

### Change Styling

Modify the `<style>` section in `renderTemplate()`:
- Colors
- Fonts
- Layout
- Spacing

### Add More Instructions

Add to the "Setup Instructions" section:
- Additional steps
- Links to documentation
- Video tutorials
- Support resources

## Files Modified

1. **classes/Services/EmailServices/InstanceProvisionedEmail.php** (NEW)
   - Email handler class

2. **classes/Services/EmailService.php**
   - Added `sendInstanceProvisionedEmail()` method
   - Added import for InstanceProvisionedEmail

3. **public/verify-payment.php**
   - Added instance provisioned email sending
   - Conditional on port allocation

4. **public/webhook-payment.php**
   - Added instance provisioned email sending
   - Conditional on port allocation

5. **test-instance-provisioned-email.php** (NEW)
   - Manual test script

## Email Statistics

- **Template Size:** ~6,831 characters (HTML)
- **Plain Text Size:** ~1,200 characters
- **Sections:** 6 main sections
- **Links:** 2 (instance URL, My Port page)
- **Images:** 0 (emoji only)
- **Responsive:** Yes

## Best Practices

1. **Always send after port allocation**
   - Check `$result['port_allocated']` is true
   - Verify `$result['port']['instance_url']` exists

2. **Don't fail payment if email fails**
   - Wrap in try-catch
   - Log errors but continue
   - Payment success is priority

3. **Log everything**
   - Email sent/failed
   - Instance URL
   - Customer email
   - Helps with debugging

4. **Test regularly**
   - Use test script
   - Verify email delivery
   - Check spam folders
   - Validate links work

## Troubleshooting

### Email Not Received

1. **Check Logs:**
   ```bash
   tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log | grep "instance provisioned"
   ```

2. **Verify Port Allocation:**
   - Check if `port_allocated` is true
   - Verify instance URL exists

3. **Check Spam Folder:**
   - Instance emails may be flagged as spam
   - Add sender to safe list

4. **Test SMTP:**
   ```bash
   php test-smtp-connection.php customer@email.com
   ```

### Email Sent But Links Don't Work

1. **Check APP_URL:**
   - Verify `$_ENV['APP_URL']` is set correctly
   - Should be full domain (https://yourdomain.com)

2. **Verify My Port URL:**
   - Test the link manually
   - Ensure page is accessible

3. **Check Instance URL:**
   - Verify port allocation worked
   - Test instance URL manually

### Template Issues

1. **Test Template Rendering:**
   ```bash
   php test-instance-provisioned-email.php
   ```

2. **Check for Errors:**
   - PHP syntax errors
   - Missing variables
   - Broken HTML

## Future Enhancements

Potential improvements:

1. **Add Screenshots**
   - Show what to expect
   - Visual setup guide

2. **Video Tutorial Link**
   - Getting started video
   - Walkthrough

3. **Estimated Setup Time**
   - "Ready in 5 minutes"
   - Set expectations

4. **Quick Start Checklist**
   - Interactive checklist
   - Track progress

5. **Support Chat Link**
   - Direct support access
   - Live chat option

## Summary

✅ **Instance Provisioned Email is fully implemented and working!**

- Separate from payment confirmation
- Sent only when instance is ready
- Includes instance URL and setup instructions
- Secure (no credentials in email)
- Beautiful, professional template
- Integrated with payment flow
- Tested and verified

**The email will be sent automatically when a customer's instance is provisioned after payment.**
