# Ticket Notification Email Implementation

## Overview

The ticket notification email system sends automated emails when a user creates a support ticket. It sends two emails:

1. **Customer Confirmation Email** - Sent to the customer who created the ticket
2. **Admin Notification Email** - Sent to the website notification email address

## Features

### Customer Confirmation Email
- Confirms ticket receipt with ticket number
- Shows ticket details (subject, category, priority, description)
- Informs customer they will be contacted via email or registered mobile number
- Professional, branded email template
- Plain text alternative for email clients that don't support HTML

### Admin Notification Email
- Full ticket details with priority and category badges
- Customer contact information (name, email, phone)
- Color-coded priority levels (High/Urgent = Red, Medium = Orange, Low/Normal = Green)
- Timestamp of ticket submission

## Usage

### Basic Implementation

```php
use Karyalay\Services\EmailService;

// Prepare ticket data
$ticketData = [
    'ticket_id' => 'TKT-20241211-1234',
    'customer_name' => 'John Doe',
    'customer_email' => 'customer@example.com',
    'customer_phone' => '+1 (555) 123-4567',
    'subject' => 'Unable to access my account',
    'description' => 'I am having trouble logging into my account...',
    'priority' => 'High', // Low, Normal, Medium, High, Urgent
    'category' => 'Account Access',
];

// Send notification emails
$emailService = EmailService::getInstance();
$result = $emailService->sendTicketNotification($ticketData);

if ($result) {
    echo "Ticket notification emails sent successfully!";
} else {
    echo "Failed to send ticket notification emails";
}
```

### Integration with Ticket Creation Form

```php
// Example: In your ticket creation handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $customerName = sanitize($_POST['name']);
    $customerEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $customerPhone = sanitize($_POST['phone']);
    $subject = sanitize($_POST['subject']);
    $description = sanitize($_POST['description']);
    $priority = sanitize($_POST['priority']);
    $category = sanitize($_POST['category']);
    
    // Generate ticket ID
    $ticketId = 'TKT-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Save ticket to database
    $ticketModel = new Ticket();
    $ticketSaved = $ticketModel->create([
        'ticket_id' => $ticketId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'subject' => $subject,
        'description' => $description,
        'priority' => $priority,
        'category' => $category,
        'status' => 'Open',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($ticketSaved) {
        // Send notification emails
        $emailService = EmailService::getInstance();
        $emailService->sendTicketNotification([
            'ticket_id' => $ticketId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'subject' => $subject,
            'description' => $description,
            'priority' => $priority,
            'category' => $category,
        ]);
        
        // Redirect to success page
        header('Location: /ticket-success.php?ticket_id=' . $ticketId);
        exit;
    }
}
```

## Required Data Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ticket_id` | string | Yes | Unique ticket identifier |
| `customer_name` | string | Yes | Customer's full name |
| `customer_email` | string | Yes | Customer's email address |
| `customer_phone` | string | No | Customer's phone number |
| `subject` | string | Yes | Ticket subject/title |
| `description` | string | Yes | Detailed ticket description |
| `priority` | string | No | Priority level (Low, Normal, Medium, High, Urgent) |
| `category` | string | No | Ticket category (General, Technical, Billing, etc.) |

## Priority Levels

The system supports the following priority levels with color coding:

- **Low** - Green badge
- **Normal** - Green badge (default)
- **Medium** - Orange badge
- **High** - Red badge
- **Urgent** - Red badge

## Category Examples

Common ticket categories you can use:

- General Inquiry
- Technical Support
- Billing & Payments
- Account Access
- Feature Request
- Bug Report
- Product Information

## Email Configuration

The ticket notification system uses the same SMTP configuration as other emails in the system. Ensure your SMTP settings are configured in:

1. Database settings (preferred)
2. Environment variables (`.env` file)

### Admin Notification Email

The admin notification is sent to the email address configured in:

1. `notifications_email` setting (database)
2. `contact_email` setting (database fallback)
3. `ADMIN_EMAIL` environment variable (final fallback)

## Testing

Use the provided test script to verify the implementation:

```bash
php test-ticket-notification.php
```

**Before testing:**
1. Update the `customer_email` in the test script to your email address
2. Ensure SMTP settings are configured
3. Verify the notifications email is set in your settings

## Error Handling

The system logs errors to the PHP error log. Check logs if emails fail to send:

```bash
tail -f /path/to/error.log
```

Common issues:
- Invalid customer email address
- SMTP configuration errors
- Missing required fields in ticket data
- Notifications email not configured

## Email Templates

Both customer and admin emails include:

- **HTML version** - Fully styled, responsive email template
- **Plain text version** - Fallback for email clients that don't support HTML

The templates are embedded in the `TicketNotificationEmail` class and can be customized by editing:
- `classes/Services/EmailServices/TicketNotificationEmail.php`

## Best Practices

1. **Always validate email addresses** before sending
2. **Sanitize user input** to prevent XSS in email content
3. **Generate unique ticket IDs** to avoid conflicts
4. **Log ticket creation** for audit trail
5. **Handle email failures gracefully** - don't block ticket creation if email fails
6. **Test with real email addresses** before going live
7. **Monitor email delivery** to ensure customers receive confirmations

## Example Ticket Creation Flow

```
User submits ticket form
    ↓
Validate input data
    ↓
Generate unique ticket ID
    ↓
Save ticket to database
    ↓
Send notification emails
    ├─→ Customer confirmation email
    └─→ Admin notification email
    ↓
Show success message to user
```

## Customization

### Changing Email Templates

Edit the template methods in `TicketNotificationEmail.php`:

- `renderCustomerTemplate()` - Customer HTML email
- `renderCustomerPlainText()` - Customer plain text email
- `renderAdminTemplate()` - Admin HTML email
- `renderAdminPlainText()` - Admin plain text email

### Adding Custom Fields

To add custom fields to the ticket notification:

1. Add the field to your `$ticketData` array
2. Update the template methods to include the new field
3. Update the documentation to reflect the new field

## Support

For issues or questions about the ticket notification system:

1. Check the error logs
2. Verify SMTP configuration
3. Test with the provided test script
4. Review the email service documentation

## Related Files

- `classes/Services/EmailService.php` - Main email service facade
- `classes/Services/EmailServices/TicketNotificationEmail.php` - Ticket notification handler
- `classes/Services/EmailServices/AbstractEmailHandler.php` - Base email handler
- `test-ticket-notification.php` - Test script
