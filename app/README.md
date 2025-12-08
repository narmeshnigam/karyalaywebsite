# Customer Portal

This directory contains the customer portal pages for authenticated users.

## Structure

```
app/
├── .htaccess                    # Access control configuration
├── dashboard.php                # Main dashboard page
├── subscription.php             # Subscription management
├── profile.php                  # User profile
├── security.php                 # Security settings
├── billing/
│   ├── .htaccess
│   └── history.php              # Billing history
└── support/
    ├── .htaccess
    └── tickets.php              # Support tickets list
```

## Layout Components

The customer portal uses a consistent layout with:

### Header Template (`templates/customer-header.php`)
- Sidebar navigation with links to:
  - Dashboard
  - Subscription
  - Billing
  - Profile
  - Support
- Top bar with:
  - Page title
  - User menu dropdown
  - Mobile menu toggle

### Footer Template (`templates/customer-footer.php`)
- Copyright information
- Quick links to help resources

## Styling

The customer portal uses custom CSS defined in `assets/css/customer-portal.css` which includes:

- Sidebar navigation styles
- Dashboard card components
- Info boxes for displaying data
- Quick action buttons
- Responsive design for mobile/tablet/desktop
- Alert messages
- Status badges

## JavaScript

Customer portal interactions are handled by `assets/js/customer-portal.js`:

- Mobile sidebar toggle
- User menu dropdown
- Auto-dismissing alerts
- Responsive behavior

## Authentication

All pages in the customer portal require authentication. The authentication check is performed in `templates/customer-header.php`:

```php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
```

## Usage

To create a new customer portal page:

1. Create a new PHP file in the appropriate directory
2. Start the session: `session_start();`
3. Set page variables: `$page_title = 'Your Page Title';`
4. Include the header: `require_once __DIR__ . '/../templates/customer-header.php';`
5. Add your page content
6. Include the footer: `require_once __DIR__ . '/../templates/customer-footer.php';`

### Example

```php
<?php
session_start();
$page_title = 'My Page';
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">My Page Title</h2>
</div>

<!-- Your content here -->

<?php
require_once __DIR__ . '/../templates/customer-footer.php';
?>
```

## Navigation Sections

The sidebar navigation automatically highlights the active section based on the current URL:

- `dashboard` - Dashboard page
- `subscription` - Subscription, plans, and setup pages
- `billing` - Billing history and invoices
- `profile` - Profile and security settings
- `support` - Support tickets

## Responsive Design

The layout is fully responsive with breakpoints at:

- Desktop: > 1024px (sidebar always visible)
- Tablet: 768px - 1024px (collapsible sidebar)
- Mobile: < 768px (collapsible sidebar, simplified layout)

## Components

### Dashboard Cards

```php
<div class="customer-portal-dashboard-grid">
    <div class="customer-portal-card">
        <div class="customer-portal-card-header">
            <h3 class="customer-portal-card-title">Card Title</h3>
            <span class="customer-portal-card-icon">📊</span>
        </div>
        <p class="customer-portal-card-value">Value</p>
        <p class="customer-portal-card-description">Description</p>
        <a href="#" class="customer-portal-card-link">Link →</a>
    </div>
</div>
```

### Info Boxes

```php
<div class="info-box">
    <h3 class="info-box-title">Box Title</h3>
    <div class="info-box-content">
        <div class="info-box-row">
            <span class="info-box-label">Label</span>
            <span class="info-box-value">Value</span>
        </div>
    </div>
</div>
```

### Quick Actions

```php
<div class="quick-actions">
    <a href="#" class="quick-action-btn">
        <span class="quick-action-icon">📦</span>
        <span class="quick-action-text">Action Text</span>
    </a>
</div>
```

### Status Badges

```php
<span class="subscription-status active">Active</span>
<span class="subscription-status expired">Expired</span>
<span class="subscription-status pending">Pending</span>
```

## Requirements

This implementation satisfies Requirement 5.1:
- Customer portal layout with navigation
- Navigation items for dashboard, subscription, billing, profile, and support
- Responsive design for all devices
- Consistent styling and user experience
