# Karyalay Design System

## Overview

A comprehensive, responsive design system for the SellerPortal System built with vanilla CSS and JavaScript.

## What Was Implemented

### CSS Architecture (Modular Approach)

1. **variables.css** - CSS custom properties for:
   - Color palette (primary, secondary, success, warning, danger, info)
   - Typography (font families, sizes, weights, line heights)
   - Spacing scale (1-20 units)
   - Border radius values
   - Box shadows
   - Transitions
   - Responsive breakpoints

2. **reset.css** - CSS reset and base styles:
   - Box-sizing reset
   - Base typography
   - Heading styles
   - Link styles
   - Image defaults

3. **layout.css** - Layout utilities:
   - Container system with responsive max-widths
   - Grid system (1-4 columns with responsive variants)
   - Flexbox utilities
   - Spacing utilities (margin, padding)
   - Display utilities
   - Text alignment utilities

4. **components.css** - Reusable components:
   - Buttons (primary, secondary, outline, danger with sizes)
   - Cards (with header, body, footer)
   - Forms (inputs, textareas, selects, labels, errors)
   - Alerts (success, warning, danger, info)
   - Badges (multiple variants)
   - Tables
   - Navigation
   - Pagination
   - Loading spinner
   - Modal overlay

5. **header.css** - Header and navigation:
   - Sticky header with shadow
   - Desktop navigation
   - Mobile menu with hamburger toggle
   - Responsive breakpoints
   - Active state highlighting

6. **footer.css** - Footer styles:
   - Multi-column footer layout
   - Responsive grid
   - Footer links and social icons
   - Copyright section

7. **main.css** - Main stylesheet:
   - Imports all CSS modules
   - Hero section styles
   - Section styles
   - Utility classes
   - Responsive image utilities

### JavaScript

**navigation.js** - Interactive navigation:
- Mobile menu toggle functionality
- Hamburger animation
- Click outside to close
- Auto-close on window resize
- Active link highlighting
- Smooth scroll for anchor links
- Body scroll prevention when menu open

### PHP Templates

1. **header.php** - Site header template:
   - Semantic HTML5 structure
   - Responsive navigation
   - Desktop and mobile menus
   - Session-aware (shows login/logout based on auth state)
   - Active page highlighting
   - Accessibility attributes (ARIA labels, roles)

2. **footer.php** - Site footer template:
   - Multi-column footer layout
   - Product, company, and support links
   - Social media links
   - Copyright with dynamic year
   - Closes page wrapper and main content

3. **template_helpers.php** - PHP utility functions:
   - `include_header()` - Include header with custom title/description
   - `include_footer()` - Include footer with custom JS
   - `render_template()` - Render template partials
   - `get_current_page()` - Get current page name
   - `is_active_page()` - Check if page is active
   - `active_class()` - Generate active class
   - Sanitization functions (esc_html, esc_attr, esc_url)
   - `format_date()` - Format dates for display
   - `truncate_text()` - Truncate text with ellipsis
   - `generate_slug()` - Generate URL-friendly slugs
   - Flash message functions
   - Authentication helpers (is_logged_in, has_role, is_admin)

### Example Pages

1. **example-page.php** - Component showcase:
   - Demonstrates all design system components
   - Shows button variants and sizes
   - Card layouts
   - Form elements
   - Alerts and badges
   - Tables
   - Complete working examples

2. **Updated index.php** - Home page:
   - Uses new template system
   - Hero section
   - Features overview
   - Modules preview
   - Call-to-action section
   - Fully responsive

### Documentation

**templates/README.md** - Complete documentation:
- Usage instructions
- Component examples
- Helper function reference
- Accessibility guidelines
- Browser support information

## Responsive Breakpoints

- **Mobile**: < 640px (default)
- **Tablet**: >= 768px
- **Desktop**: >= 1024px
- **Large Desktop**: >= 1280px

## Key Features

✅ **Fully Responsive** - Works on mobile, tablet, and desktop
✅ **Accessible** - ARIA labels, semantic HTML, keyboard navigation
✅ **Modular CSS** - Easy to maintain and extend
✅ **Reusable Components** - Consistent UI across the application
✅ **PHP Template System** - DRY principle with includes
✅ **Mobile-First** - Progressive enhancement approach
✅ **Performance** - Minimal CSS/JS, no external dependencies
✅ **Browser Compatible** - Works in all modern browsers

## Requirements Validated

This implementation satisfies:
- **Requirement 14.1**: Responsive layout for mobile, tablet, desktop
- **Requirement 14.2**: Responsive layout for tablet devices
- **Requirement 14.3**: Responsive layout for desktop devices
- **Requirement 14.4**: Form labels for accessibility (implemented in components)
- **Requirement 14.5**: Error messages with ARIA attributes (implemented in components)

## Usage Example

```php
<?php
session_start();
require_once __DIR__ . '/../includes/template_helpers.php';

$page_title = 'My Page';
$page_description = 'Page description';

include_header($page_title, $page_description);
?>

<div class="container">
    <h1>Page Content</h1>
    <!-- Your content here -->
</div>

<?php include_footer(); ?>
```

## Next Steps

The design system is now ready for use in:
- Public website pages (modules, features, pricing, etc.)
- Customer portal pages
- Admin panel pages
- Authentication pages (login, register)

All future pages should use this template system for consistency.
