# Reusable CTA Component Usage Guide

## Overview
The reusable CTA (Call-to-Action) component is a lead capture form that can be used across different pages of the website. It automatically saves leads to the database and provides a consistent user experience.

## Features
- ✅ Responsive design with dark gradient background
- ✅ AJAX form submission (no page reload)
- ✅ CSRF protection
- ✅ Source tracking (tracks which page the lead came from)
- ✅ Email validation
- ✅ Success/error messaging
- ✅ Accessible form fields
- ✅ Admin panel for viewing and managing leads

## Files Created

### Frontend
- `templates/cta-form.php` - Reusable CTA form component
- `public/submit-lead.php` - Form submission handler (AJAX endpoint)

### Backend
- `classes/Models/Lead.php` - Lead model for database operations
- `database/migrations/030_create_leads_table.sql` - Database migration

### Admin Panel
- `admin/leads.php` - Leads management page (list view with filters)
- `admin/leads/view.php` - Individual lead detail view

## Database Schema

The `leads` table includes:
- `id` - Unique identifier (UUID)
- `name` - Lead's name (required)
- `email` - Lead's email (required)
- `phone` - Lead's phone number (optional)
- `company` - Lead's company name (optional)
- `message` - Lead's message (optional)
- `source` - Page where lead was captured (e.g., "homepage", "pricing")
- `status` - Lead status (NEW, CONTACTED, QUALIFIED, CONVERTED, LOST)
- `created_at` - Timestamp when lead was created
- `updated_at` - Timestamp when lead was last updated

## Usage

### Basic Usage

Include the component in any PHP page:

```php
<?php
include __DIR__ . '/../templates/cta-form.php';
?>
```

### Custom Title and Subtitle

```php
<?php
$cta_title = "Ready to Get Started?";
$cta_subtitle = "Contact us today for a free consultation";
$cta_source = "pricing-page";
include __DIR__ . '/../templates/cta-form.php';
?>
```

### Available Variables

- `$cta_title` - Custom heading (default: "Ready to Transform Your Business?")
- `$cta_subtitle` - Custom subtitle text
- `$cta_source` - Source identifier for tracking (default: current page URL)

## Example Implementations

### Homepage
```php
<?php
$cta_title = "Ready to Transform Your Business?";
$cta_subtitle = "Get in touch with us today and discover how Karyalay can streamline your operations";
$cta_source = "homepage";
include __DIR__ . '/../templates/cta-form.php';
?>
```

### Pricing Page
```php
<?php
$cta_title = "Start Your Free Trial Today";
$cta_subtitle = "No credit card required. Get started in minutes.";
$cta_source = "pricing";
include __DIR__ . '/../templates/cta-form.php';
?>
```

### Solutions Page
```php
<?php
$cta_title = "Interested in This Solution?";
$cta_subtitle = "Let's discuss how this solution can benefit your business";
$cta_source = "solution-" . $solution['slug'];
include __DIR__ . '/../templates/cta-form.php';
?>
```

## Admin Panel

### Viewing Leads

1. Navigate to **Admin Panel → Marketing → Leads**
2. View all leads with filtering options:
   - All Leads
   - New
   - Contacted
   - Qualified
   - Converted
   - Lost

### Lead Details

Click "View" on any lead to see:
- Full contact information
- Message content
- Lead source
- Current status
- Timestamps
- Quick action buttons (Email, Call)

### Lead Status Workflow

1. **NEW** - Initial status when lead is captured
2. **CONTACTED** - After first contact attempt
3. **QUALIFIED** - Lead shows genuine interest
4. **CONVERTED** - Lead became a customer
5. **LOST** - Lead is no longer interested

## Form Fields

### Required Fields
- Name
- Email

### Optional Fields
- Phone
- Company
- Message

## Security Features

- CSRF token validation
- Email validation
- Input sanitization
- SQL injection protection (via PDO prepared statements)
- XSS protection (via htmlspecialchars)

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Thank you for your interest! We'll be in touch with you shortly."
}
```

### Error Response
```json
{
  "success": false,
  "message": "Please check your input and try again.",
  "errors": {
    "email": "Please enter a valid email address"
  }
}
```

## Customization

### Styling

The component includes inline styles that can be customized by:
1. Editing `templates/cta-form.php` directly
2. Overriding styles in your main CSS file
3. Using CSS custom properties (variables)

### Form Behavior

Modify the JavaScript in `templates/cta-form.php` to:
- Change success message display duration
- Add custom validation
- Integrate with analytics
- Add additional form fields

## Testing

To test the component:

1. Visit a page with the CTA form
2. Fill out the form with test data
3. Submit the form
4. Check for success message
5. Verify lead appears in Admin Panel → Leads

## Troubleshooting

### Form Not Submitting
- Check browser console for JavaScript errors
- Verify CSRF token is being generated
- Check `public/submit-lead.php` for PHP errors

### Leads Not Appearing in Admin
- Verify database migration ran successfully
- Check database connection
- Review error logs

### Email Validation Failing
- Ensure email format is valid
- Check for whitespace in email field

## Future Enhancements

Potential improvements:
- Email notifications when new leads arrive
- Lead assignment to team members
- Lead scoring system
- Integration with CRM systems
- Export leads to CSV
- Lead activity timeline
- Automated follow-up reminders
