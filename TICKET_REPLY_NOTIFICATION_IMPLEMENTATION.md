# Ticket Reply Notification Email Implementation

## Overview

The ticket reply notification system sends automated emails to customers when an admin responds to their support ticket. The email notifies the customer that a response is available but does NOT include the reply content itself, encouraging customers to log in and view the response on the website.

## Features

### Customer Notification Email
- Notifies customer that a response is available
- Shows ticket ID and subject for reference
- Includes direct link to view the response
- Does NOT show the actual reply content (for privacy and engagement)
- Professional, branded email template
- Plain text alternative for email clients that don't support HTML

### Key Design Decisions
1. **No Reply Content in Email** - Reply content is not included to:
   - Maintain privacy and security
   - Drive customer engagement on the platform
   - Ensure customers see the full context and conversation history
   - Prevent email thread confusion

2. **Automatic Notification** - Emails are sent automatically when:
   - Admin adds a public reply (visible to customer)
   - NOT sent for internal notes (admin-only)

## Usage

### Basic Implementation (Automatic)

When an admin replies to a ticket through the admin panel, the notification is sent automatically:

```php
use Karyalay\Services\TicketService;

$ticketService = new TicketService();

// Add admin reply - automatically sends notification email
$result = $ticketService->addAdminReply(
    $ticketId,
    $adminUserId,
    $replyContent,
    false // isInternal = false (sends notification)
);

if ($result['success']) {
    echo "Reply added and customer notified!";
}
```

### Manual Email Sending

If you need to send the notification manually:

```php
use Karyalay\Services\EmailService;

$replyData = [
    'ticket_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
    'customer_name' => 'John Doe',
    'customer_email' => 'customer@example.com',
    'ticket_subject' => 'Unable to access my account',
    'ticket_url' => 'https://yoursite.com/app/support/tickets/view.php?id=...',
];

$emailService = EmailService::getInstance();
$result = $emailService->sendTicketReplyNotification($replyData);
```

### Integration in Admin Panel

The admin ticket view page (`admin/support/tickets/view.php`) automatically handles this:

```php
// When admin submits a reply
if ($action === 'add_reply') {
    $content = trim($_POST['content'] ?? '');
    
    // This method adds the reply AND sends notification
    $result = $ticketService->addAdminReply(
        $ticket_id,
        $_SESSION['user_id'],
        $content,
        false // Not internal - customer will be notified
    );
    
    if ($result['success']) {
        $success_message = 'Reply added successfully. Customer has been notified via email.';
    }
}

// When admin adds internal note
if ($action === 'add_internal_note') {
    $content = trim($_POST['content'] ?? '');
    
    // This method adds the note but does NOT send notification
    $result = $ticketService->addAdminReply(
        $ticket_id,
        $_SESSION['user_id'],
        $content,
        true // Internal note - NO notification sent
    );
}
```

## Required Data Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ticket_id` | string | Yes | Ticket UUID |
| `customer_name` | string | Yes | Customer's full name |
| `customer_email` | string | Yes | Customer's email address |
| `ticket_subject` | string | Yes | Ticket subject/title |
| `ticket_url` | string | Yes | Direct link to view ticket |

## Email Flow

```
Admin submits reply via admin panel
    ↓
TicketService::addAdminReply() called
    ↓
Reply saved to database via addReply()
    ↓
Check if reply is internal
    ├─→ If internal: Skip notification
    └─→ If public: Continue to notification
    ↓
TicketService::sendReplyNotification() called
    ↓
Fetch ticket and customer details
    ↓
Build ticket URL
    ↓
EmailService::sendTicketReplyNotification() called
    ↓
TicketReplyNotificationEmail::sendEmail() executes
    ↓
Email sent to customer
    ↓
Success message shown to admin
```

## Email Content

### What's Included:
- ✅ Notification that a response is available
- ✅ Ticket ID (formatted, e.g., A1B2C3D4)
- ✅ Ticket subject
- ✅ Direct link to view the response
- ✅ Instructions on how to continue the conversation
- ✅ Professional branding

### What's NOT Included:
- ❌ The actual reply content
- ❌ Admin name who replied
- ❌ Reply timestamp
- ❌ Previous conversation history

## Ticket URL Generation

The ticket URL is automatically generated using:

```php
$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
$ticketUrl = rtrim($baseUrl, '/') . '/app/support/tickets/view.php?id=' . urlencode($ticket['id']);
```

**Important:** Ensure `APP_URL` is set in your `.env` file:

```env
APP_URL=https://yoursite.com
```

## Ticket ID Format

- **Database:** Full UUID (e.g., `a1b2c3d4-e5f6-7890-abcd-ef1234567890`)
- **Email Display:** First 8 characters uppercase (e.g., `A1B2C3D4`)
- **Subject Line:** `New Response on Ticket #A1B2C3D4`

## Internal Notes vs Public Replies

### Public Reply (Customer Notified)
```php
$ticketService->addAdminReply($ticketId, $adminId, $content, false);
// isInternal = false → Email notification sent
```

### Internal Note (No Notification)
```php
$ticketService->addAdminReply($ticketId, $adminId, $content, true);
// isInternal = true → NO email notification
```

## Error Handling

The system logs errors but doesn't block reply creation:

- Missing customer email → Logged, no email sent
- Customer not found → Logged, no email sent
- Email sending failure → Logged, reply still saved
- All errors logged with context for debugging

## Configuration

### Email Settings
Uses existing SMTP configuration from:
1. Database settings (preferred)
2. Environment variables (`.env` file)

### APP_URL Configuration
Set in `.env` file for correct ticket URLs:

```env
APP_URL=https://yoursite.com
```

## Testing

### Test Script
Use the provided test script to verify email functionality:

```bash
php test-ticket-reply-notification.php
```

**Before testing:**
1. Update the `customer_email` in the test script
2. Ensure SMTP settings are configured
3. Verify `APP_URL` is set in `.env`

### Manual Testing
1. Log in as admin
2. Navigate to a ticket
3. Add a public reply
4. Check:
   - Success message mentions customer notification
   - Customer receives notification email
   - Email contains ticket details and link
   - Email does NOT contain reply content
   - Link directs to correct ticket page

## Benefits

1. **Customer Engagement** - Drives customers back to the platform
2. **Privacy & Security** - Reply content not exposed in email
3. **Context Preservation** - Customers see full conversation history
4. **Reduced Email Clutter** - Simple notification vs full conversation
5. **Professional Communication** - Branded, well-formatted emails
6. **Selective Notifications** - Internal notes don't trigger emails

## Admin Experience

### Success Messages

**Public Reply:**
```
Reply added successfully. Customer has been notified via email.
```

**Internal Note:**
```
Internal note added successfully.
```

This clearly indicates whether a notification was sent.

## Customer Experience

1. Customer receives email notification
2. Email shows ticket ID and subject
3. Customer clicks "View Response" button
4. Redirected to ticket page (must log in if not already)
5. Customer sees full conversation with new reply
6. Customer can respond directly on the platform

## Best Practices

1. **Use Internal Notes for Admin Communication** - Don't notify customers unnecessarily
2. **Provide Clear Responses** - Customers will see the full reply on the platform
3. **Monitor Email Delivery** - Check logs if customers report not receiving notifications
4. **Keep APP_URL Updated** - Ensure ticket links work correctly
5. **Test Before Production** - Verify emails and links work as expected

## Troubleshooting

### Customer Not Receiving Emails

1. Check SMTP configuration
2. Verify customer email address is valid
3. Check spam/junk folders
4. Review error logs for email sending failures
5. Test with `test-ticket-reply-notification.php`

### Incorrect Ticket Links

1. Verify `APP_URL` in `.env` file
2. Ensure URL includes protocol (https://)
3. Check for trailing slashes
4. Test link manually

### Emails Sent for Internal Notes

1. Verify `isInternal` parameter is `true`
2. Check admin panel form submission
3. Review `addAdminReply` method calls

## Related Files

- `classes/Services/TicketService.php` - Ticket service with reply methods
- `classes/Services/EmailService.php` - Email service facade
- `classes/Services/EmailServices/TicketReplyNotificationEmail.php` - Reply notification handler
- `admin/support/tickets/view.php` - Admin ticket view page
- `test-ticket-reply-notification.php` - Test script

## API Reference

### TicketService::addAdminReply()

```php
public function addAdminReply(
    string $ticketId,
    string $adminId,
    string $content,
    bool $isInternal = false
): array
```

**Parameters:**
- `$ticketId` - Ticket UUID
- `$adminId` - Admin user UUID
- `$content` - Reply content
- `$isInternal` - Whether this is an internal note (default: false)

**Returns:**
```php
[
    'success' => true,
    'message' => [...] // Message data
]
```

### EmailService::sendTicketReplyNotification()

```php
public function sendTicketReplyNotification(array $replyData): bool
```

**Parameters:**
```php
$replyData = [
    'ticket_id' => string,
    'customer_name' => string,
    'customer_email' => string,
    'ticket_subject' => string,
    'ticket_url' => string
]
```

**Returns:** `bool` - True if email sent successfully

## Future Enhancements

Potential improvements:
- Digest emails for multiple replies
- Reply preview (first 100 characters)
- Customizable email templates via admin panel
- SMS notifications option
- Email preferences per customer
- Reply time tracking and SLA notifications

## Support

For issues or questions:
1. Check error logs
2. Verify SMTP configuration
3. Test with provided test script
4. Review this documentation
5. Check related email service documentation
