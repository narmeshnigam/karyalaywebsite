# Payment Settings Management Guide

## Overview
The payment settings feature allows administrators to configure Razorpay payment gateway credentials through the admin panel instead of hardcoding them in configuration files.

## Features

### 1. Admin Interface
- **Location**: `/karyalayportal/admin/payment-settings.php`
- **Access**: Admin authentication required
- **Purpose**: Configure and manage payment gateway credentials

### 2. Settings Stored
The following settings are stored in the `settings` table:

| Setting Key | Description | Required |
|------------|-------------|----------|
| `razorpay_key_id` | Razorpay API Key ID | Yes |
| `razorpay_key_secret` | Razorpay API Key Secret | Yes |
| `razorpay_webhook_secret` | Webhook verification secret | No |
| `payment_mode` | Test or Live mode | Yes |
| `payment_gateway` | Payment gateway name (razorpay) | Yes |

### 3. Security Features
- **CSRF Protection**: Form submissions are protected with CSRF tokens
- **Password Fields**: Sensitive credentials are masked by default
- **Toggle Visibility**: Show/hide password fields with eye icon
- **Secure Storage**: Credentials stored in database with proper encryption
- **Admin Only**: Only authenticated admins can access settings

## How to Use

### Step 1: Access Payment Settings
1. Log in to the admin panel
2. Navigate to **Payment Settings** from the admin menu
3. Or directly visit: `/karyalayportal/admin/payment-settings.php`

### Step 2: Configure Payment Mode
Choose between:
- **Test Mode**: For development and testing (use test API keys)
- **Live Mode**: For production payments (use live API keys)

### Step 3: Enter Razorpay Credentials

#### Get Your Credentials:
1. Log in to [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Go to **Settings → API Keys**
3. Generate or copy your:
   - Key ID (starts with `rzp_test_` or `rzp_live_`)
   - Key Secret (keep confidential)

#### Enter Credentials:
1. **Razorpay Key ID**: Paste your Key ID
2. **Razorpay Key Secret**: Paste your Key Secret
3. **Webhook Secret** (Optional): For payment notifications

### Step 4: Save Settings
Click **Save Settings** to store the configuration

## Technical Implementation

### Database Storage
Settings are stored in the `settings` table with key-value pairs:

```sql
CREATE TABLE settings (
    id CHAR(36) PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Settings Model
The `Setting` model provides methods to:
- `get($key, $default)` - Retrieve a setting value
- `set($key, $value, $type)` - Store a setting value
- `getMultiple($keys)` - Retrieve multiple settings
- `setMultiple($settings, $type)` - Store multiple settings
- `delete($key)` - Remove a setting

### PaymentService Integration
The `PaymentService` class automatically:
1. Loads credentials from database settings
2. Falls back to config file if not in database
3. Throws exception if credentials are missing

```php
// Credentials are loaded automatically
$paymentService = new PaymentService();

// No need to pass credentials manually
$order = $paymentService->createPaymentOrder($orderData);
```

## API Credentials

### Test Mode Credentials
- **Key ID Format**: `rzp_test_xxxxxxxxxx`
- **Purpose**: Development and testing
- **Payments**: Test payments only (no real money)

### Live Mode Credentials
- **Key ID Format**: `rzp_live_xxxxxxxxxx`
- **Purpose**: Production payments
- **Payments**: Real money transactions
- **Requirements**: KYC verification required

## Webhook Configuration

### Setup Webhook in Razorpay:
1. Go to **Settings → Webhooks** in Razorpay Dashboard
2. Click **Create New Webhook**
3. Enter webhook URL: `https://yourdomain.com/karyalayportal/webhook/razorpay.php`
4. Select events to listen for:
   - `payment.authorized`
   - `payment.captured`
   - `payment.failed`
   - `order.paid`
5. Copy the **Webhook Secret**
6. Paste it in the Payment Settings page

## Security Best Practices

### 1. Credential Management
- ✅ Never commit credentials to version control
- ✅ Use test credentials during development
- ✅ Rotate credentials periodically
- ✅ Limit access to admin panel

### 2. Environment Separation
- **Development**: Use test mode credentials
- **Staging**: Use test mode credentials
- **Production**: Use live mode credentials

### 3. Access Control
- Only administrators can view/edit payment settings
- CSRF tokens protect against unauthorized changes
- All changes are logged for audit trail

## Troubleshooting

### Issue: "Razorpay credentials not configured"
**Solution**: 
1. Go to Payment Settings page
2. Enter valid Razorpay credentials
3. Save settings

### Issue: Payment fails with authentication error
**Solution**:
1. Verify Key ID and Key Secret are correct
2. Check if using correct mode (test/live)
3. Ensure credentials match the payment mode

### Issue: Webhook verification fails
**Solution**:
1. Verify Webhook Secret is configured
2. Check webhook URL is correct
3. Ensure webhook is active in Razorpay Dashboard

## Migration from Config File

If you have existing credentials in `config/app.php`:

### Before:
```php
// config/app.php
return [
    'razorpay_key_id' => 'rzp_test_xxxxxxxxxx',
    'razorpay_key_secret' => 'your_secret_key',
    // ...
];
```

### After:
1. Copy credentials from config file
2. Go to Payment Settings page
3. Paste credentials and save
4. (Optional) Remove from config file

The system will automatically use database settings if available, with config file as fallback.

## Admin Menu Integration

To add Payment Settings to your admin menu, update the navigation:

```php
// templates/admin-header.php
<li>
    <a href="/karyalayportal/admin/payment-settings.php">
        <svg><!-- icon --></svg>
        Payment Settings
    </a>
</li>
```

## Logging

All payment setting changes are logged:
```
Payment settings updated by admin: admin@example.com
```

Check logs at: `storage/logs/` or server error logs

## Support

For issues or questions:
1. Check Razorpay documentation: https://razorpay.com/docs/
2. Review error logs
3. Contact Razorpay support for API issues
4. Contact system administrator for access issues
