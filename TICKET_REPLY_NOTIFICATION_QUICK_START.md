# Ticket Reply Notification - Quick Start

## What It Does

When an admin replies to a customer's ticket, the customer automatically receives an email notification that a response is available. The email does NOT include the reply content - it directs them to log in and view it on the website.

## Quick Setup

### 1. Set APP_URL in .env

```env
APP_URL=https://yoursite.com
```

This is used to generate ticket links in emails.

### 2. Test It

```bash
php test-ticket-reply-notification.php
```

## How It Works

### Admin Adds Public Reply
```
Admin types reply → Clicks "Send Reply" → Customer gets email notification
```

Success message: **"Reply added successfully. Customer has been notified via email."**

### Admin Adds Internal Note
```
Admin types note → Clicks "Add Note" → NO email sent
```

Success message: **"Internal note added successfully."**

## Email Content

**Customer receives:**
- 📧 Subject: "New Response on Ticket #A1B2C3D4"
- 🎫 Ticket ID and subject
- 🔗 "View Response" button with direct link
- ❌ NO reply content (must log in to view)

## Code Usage

### Automatic (Recommended)
Already integrated in admin panel - just use the reply form!

### Manual
```php
use Karyalay\Services\TicketService;

$ticketService = new TicketService();

// Public reply - sends notification
$ticketService->addAdminReply($ticketId, $adminId, $content, false);

// Internal note - no notification
$ticketService->addAdminReply($ticketId, $adminId, $content, true);
```

## Key Points

✅ **Automatic** - No extra code needed in admin panel  
✅ **Selective** - Only public replies trigger emails  
✅ **Privacy** - Reply content NOT in email  
✅ **Engagement** - Drives customers back to platform  
✅ **Non-blocking** - Email failures don't prevent replies  

## Configuration

**Required:**
- `APP_URL` in `.env` file
- SMTP settings configured

**That's it!** The system handles everything else automatically.

## Testing Checklist

- [ ] Set `APP_URL` in `.env`
- [ ] Run test script
- [ ] Log in as admin
- [ ] Reply to a ticket
- [ ] Check customer receives email
- [ ] Verify link works
- [ ] Confirm reply content NOT in email

## Full Documentation

See `TICKET_REPLY_NOTIFICATION_IMPLEMENTATION.md` for complete details.
