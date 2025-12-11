# Ticket Notification - Quick Start Guide

## Quick Implementation

### 1. Send Ticket Notification

```php
use Karyalay\Services\EmailService;

$emailService = EmailService::getInstance();
$emailService->sendTicketNotification([
    'ticket_id' => 'TKT-20241211-1234',
    'customer_name' => 'John Doe',
    'customer_email' => 'customer@example.com',
    'customer_phone' => '+1 (555) 123-4567',
    'subject' => 'Unable to access my account',
    'description' => 'I am having trouble logging in...',
    'priority' => 'High',
    'category' => 'Account Access',
]);
```

### 2. What Gets Sent

**Customer receives:**
- ✅ Ticket confirmation with ticket number
- 📋 Full ticket details
- 📞 Message that they'll be contacted via email or phone

**Admin receives:**
- 🎫 New ticket notification
- 📋 Full ticket details with priority badges
- 👤 Customer contact information (name, email, phone)

### 3. Test It

```bash
php test-ticket-notification.php
```

## Required Fields

- `ticket_id` - Unique identifier
- `customer_name` - Customer's name
- `customer_email` - Customer's email
- `subject` - Ticket subject
- `description` - Ticket description

## Optional Fields

- `customer_phone` - Customer's phone number
- `priority` - Low, Normal, Medium, High, Urgent (default: Normal)
- `category` - General, Technical, Billing, etc. (default: General)

## Priority Levels

- **Low/Normal** → Green badge
- **Medium** → Orange badge  
- **High/Urgent** → Red badge

## Configuration

Admin notifications are sent to the email configured in:
1. Database setting: `notifications_email`
2. Fallback: `contact_email`
3. Final fallback: `ADMIN_EMAIL` env variable

## Full Documentation

See `TICKET_NOTIFICATION_IMPLEMENTATION.md` for complete details.
