# Karyalay Template System

This directory contains the reusable template components for the SellerPortal System.

## Overview

The template system provides a consistent layout and design across all pages of the application. It includes:

- **Header Template** (`header.php`) - Site header with responsive navigation
- **Footer Template** (`footer.php`) - Site footer with links and copyright
- **Template Helpers** (`../includes/template_helpers.php`) - PHP utility functions

## Usage

### Basic Page Structure

```php
<?php
// Start session
session_start();

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Set page variables
$page_title = 'My Page Title';
$page_description = 'Page description for SEO';

// Include header
include_header($page_title, $page_description);
?>

<!-- Your page content here -->
<div class="container">
    <h1>Page Content</h1>
    <p>Your content goes here...</p>
</div>

<?php
// Include footer
include_footer();
?>
```

### Adding Custom CSS/JS

```php
// Add custom CSS files
$additional_css = [
    '/assets/css/custom-page.css',
    '/assets/css/another-style.css'
];

include_header($page_title, $page_description, $additional_css);

// Add custom JS files
$additional_js = [
    '/assets/js/custom-script.js'
];

include_footer($additional_js);
```

## Design System

### CSS Architecture

The CSS is organized into modular files:

- `variables.css` - CSS custom properties (colors, spacing, typography)
- `reset.css` - CSS reset and base styles
- `layout.css` - Layout utilities (grid, flexbox, spacing)
- `components.css` - Reusable component styles
- `header.css` - Header and navigation styles
- `footer.css` - Footer styles
- `main.css` - Main stylesheet that imports all modules

### Responsive Breakpoints

```css
/* Mobile: < 640px (default) */
/* Tablet: >= 768px */
/* Desktop: >= 1024px */
/* Large Desktop: >= 1280px */
```

### Color Palette

- **Primary**: `var(--color-primary)` - #2563eb
- **Secondary**: `var(--color-secondary)` - #64748b
- **Success**: `var(--color-success)` - #10b981
- **Warning**: `var(--color-warning)` - #f59e0b
- **Danger**: `var(--color-danger)` - #ef4444
- **Info**: `var(--color-info)` - #06b6d4

### Typography

```css
/* Font Sizes */
--font-size-xs: 0.75rem;    /* 12px */
--font-size-sm: 0.875rem;   /* 14px */
--font-size-base: 1rem;     /* 16px */
--font-size-lg: 1.125rem;   /* 18px */
--font-size-xl: 1.25rem;    /* 20px */
--font-size-2xl: 1.5rem;    /* 24px */
--font-size-3xl: 1.875rem;  /* 30px */
--font-size-4xl: 2.25rem;   /* 36px */
--font-size-5xl: 3rem;      /* 48px */
```

## Components

### Buttons

```html
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-secondary">Secondary Button</button>
<button class="btn btn-outline">Outline Button</button>
<button class="btn btn-danger">Danger Button</button>

<!-- Sizes -->
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary">Default</button>
<button class="btn btn-primary btn-lg">Large</button>
```

### Cards

```html
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Card Title</h4>
    </div>
    <div class="card-body">
        <p>Card content goes here</p>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary">Action</button>
    </div>
</div>
```

### Forms

```html
<div class="form-group">
    <label for="input-id" class="form-label">Label</label>
    <input type="text" id="input-id" class="form-input" placeholder="Placeholder">
    <span class="form-help">Help text</span>
    <span class="form-error">Error message</span>
</div>
```

### Alerts

```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-info">Info message</div>
```

### Badges

```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-danger">Danger</span>
```

### Grid System

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Column 1</div>
    <div>Column 2</div>
    <div>Column 3</div>
</div>
```

## Helper Functions

### Template Helpers

```php
// Sanitization
esc_html($text);        // Sanitize for HTML output
esc_attr($text);        // Sanitize for HTML attributes
esc_url($url);          // Sanitize URLs

// Navigation
get_current_page();     // Get current page name
is_active_page($page);  // Check if page is active
active_class($page);    // Get 'active' class if page is active

// Utilities
format_date($date);     // Format date for display
truncate_text($text);   // Truncate text with ellipsis
generate_slug($text);   // Generate URL-friendly slug

// Flash Messages
set_flash_message($message, $type);  // Set flash message
display_flash_message();             // Display flash message

// Authentication
is_logged_in();         // Check if user is logged in
has_role($role);        // Check if user has role
is_admin();             // Check if user is admin
```

## Accessibility

The template system follows accessibility best practices:

- Semantic HTML5 elements (`<header>`, `<nav>`, `<main>`, `<footer>`)
- ARIA labels and attributes where appropriate
- Keyboard navigation support
- Form labels associated with inputs
- Focus states for interactive elements
- Screen reader-friendly text (`.sr-only` class)

## Mobile Navigation

The mobile menu is automatically handled by JavaScript:

- Hamburger menu toggle on mobile devices
- Smooth transitions
- Closes when clicking outside
- Closes when window is resized to desktop size
- Prevents body scroll when menu is open

## Example Page

See `/public/example-page.php` for a complete demonstration of all components and the template system in action.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)
