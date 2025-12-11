# Permission System Implementation

## Overview

A comprehensive role-based permission system has been implemented for the admin panel. Users can now have multiple roles assigned, and each role grants specific permissions to access different sections of the admin panel.

## Roles

| Role | Access |
|------|--------|
| **ADMIN** | Full system access including all settings and user management |
| **SUPPORT** | Tickets only |
| **INFRASTRUCTURE** | Plans, Ports |
| **SALES** | Leads only |
| **SALES_MANAGER** | Leads, Customers (view only, no drill-down to subscriptions/orders/tickets) |
| **OPERATIONS** | Customers, Orders, Invoices, Subscriptions |
| **CONTENT_MANAGER** | Hero Slides, Solutions, Why Choose, Testimonials, Blog Posts, Case Studies, About, Legal, FAQs, Media Library |
| **CUSTOMER** | Default role for all users (customer portal access) |

## Admin-Only Settings

The following sections are restricted to ADMIN role only:
- SMTP Integration
- Payment Integration
- Localisation
- Users & Roles
- General Settings

## Database Changes

### Migration 048: Create user_roles table

Run the migration to create the `user_roles` table:

```bash
php database/run_migration_048.php
```

This creates:
- `user_roles` table for many-to-many user-role relationships
- Updates the `users.role` ENUM to include new roles
- Migrates existing users to the new system

## Key Files Modified

### Core Permission System
- `classes/Services/RoleService.php` - Complete rewrite with new roles and multi-role support
- `includes/admin_helpers.php` - Added permission checking functions
- `templates/admin-header.php` - Dynamic menu based on user permissions

### User Management
- `admin/users-and-roles.php` - Updated to show multiple roles
- `admin/users-and-roles/edit.php` - Multi-role selection UI
- `admin/users-and-roles/new.php` - Multi-role selection UI
- `admin/users-and-roles/roles.php` - Updated roles display

### Admin Pages Updated with Permissions

#### Dashboard
- `admin/dashboard.php` - `dashboard.view`

#### Support
- `admin/support/tickets.php` - `tickets.view`
- `admin/support/tickets/view.php` - `tickets.view_details`

#### Marketing & Sales
- `admin/leads.php` - `leads.view`
- `admin/leads/view.php` - `leads.view_details`
- `admin/customers.php` - `customers.view`
- `admin/customers/view.php` - `customers.view_details`
- `admin/orders.php` - `orders.view`
- `admin/orders/view.php` - `orders.view_details`
- `admin/invoices.php` - `invoices.view`
- `admin/invoices/view.php` - `invoices.view_details`
- `admin/subscriptions.php` - `subscriptions.view`
- `admin/subscriptions/view.php` - `subscriptions.view_details`
- `admin/subscriptions/new.php` - `subscriptions.create`

#### Infrastructure
- `admin/plans.php` - `plans.view`
- `admin/plans/view.php` - `plans.view_details`
- `admin/plans/new.php` - `plans.create`
- `admin/plans/edit.php` - `plans.edit`
- `admin/plans/delete.php` - `plans.delete`
- `admin/ports.php` - `ports.view`
- `admin/ports/view.php` - `ports.view_details`
- `admin/ports/new.php` - `ports.create`
- `admin/ports/edit.php` - `ports.edit`
- `admin/ports/delete.php` - `ports.delete`
- `admin/ports/import.php` - `ports.import`
- `admin/port-allocation-logs.php` - `ports.view`

#### Content Management
- `admin/hero-slides.php` - `hero_slides.manage`
- `admin/hero-slides/new.php` - `hero_slides.manage`
- `admin/hero-slides/edit.php` - `hero_slides.manage`
- `admin/hero-slides/delete.php` - `hero_slides.manage`
- `admin/solutions.php` - `solutions.manage`
- `admin/solutions/new.php` - `solutions.manage`
- `admin/solutions/edit.php` - `solutions.manage`
- `admin/solutions/delete.php` - `solutions.manage`
- `admin/modules.php` - `solutions.manage`
- `admin/modules/new.php` - `solutions.manage`
- `admin/modules/edit.php` - `solutions.manage`
- `admin/modules/delete.php` - `solutions.manage`
- `admin/features.php` - `content.view`
- `admin/features/new.php` - `content.create`
- `admin/features/edit.php` - `content.edit`
- `admin/features/delete.php` - `content.delete`
- `admin/why-choose-cards.php` - `why_choose.manage`
- `admin/why-choose-cards/new.php` - `why_choose.manage`
- `admin/why-choose-cards/edit.php` - `why_choose.manage`
- `admin/why-choose-cards/delete.php` - `why_choose.manage`
- `admin/testimonials.php` - `testimonials.manage`
- `admin/testimonials/new.php` - `testimonials.manage`
- `admin/testimonials/edit.php` - `testimonials.manage`
- `admin/testimonials/delete.php` - `testimonials.manage`
- `admin/blog.php` - `blog.manage`
- `admin/blog/new.php` - `blog.manage`
- `admin/blog/edit.php` - `blog.manage`
- `admin/blog/delete.php` - `blog.manage`
- `admin/case-studies.php` - `case_studies.manage`
- `admin/case-studies/new.php` - `case_studies.manage`
- `admin/case-studies/edit.php` - `case_studies.manage`
- `admin/case-studies/delete.php` - `case_studies.manage`
- `admin/about-page.php` - `about.manage`
- `admin/legal.php` - `legal.manage`
- `admin/faqs.php` - `faqs.manage`
- `admin/faq-categories.php` - `faqs.manage`
- `admin/faq-edit.php` - `faqs.manage`
- `admin/media-library.php` - `media.view`

#### Settings (ADMIN only)
- `admin/smtp-settings.php` - `settings.smtp`
- `admin/payment-settings.php` - `settings.payment`
- `admin/localisation.php` - `settings.localisation`
- `admin/users-and-roles.php` - `users.view`
- `admin/users-and-roles/edit.php` - `users.edit`
- `admin/users-and-roles/new.php` - `users.create`
- `admin/users-and-roles/roles.php` - `roles.manage`
- `admin/settings/general.php` - `settings.general`
- `admin/settings/branding.php` - `settings.general`
- `admin/settings/seo.php` - `settings.general`
- `admin/settings/legal-identity.php` - `settings.general`

#### API Endpoints (Export)
- `admin/api/export-tickets.php` - `tickets.view`
- `admin/api/export-leads.php` - `leads.view`
- `admin/api/export-customers.php` - `customers.view`
- `admin/api/export-orders.php` - `orders.view`
- `admin/api/export-subscriptions.php` - `subscriptions.view`
- `admin/api/export-ports.php` - `ports.view`
- `admin/api/export-plans.php` - `plans.view`
- `admin/api/export-port-allocation-logs.php` - `ports.view`

## Usage

### Checking Permissions in PHP

```php
// Check single permission
if (has_permission('tickets.view')) {
    // User can view tickets
}

// Check any of multiple permissions
if (has_any_permission(['orders.view', 'invoices.view'])) {
    // User can view orders OR invoices
}

// Check all permissions
if (has_all_permissions(['customers.view', 'customers.edit'])) {
    // User can view AND edit customers
}

// Check role
if (has_role('ADMIN')) {
    // User is an admin
}

// Require permission (redirects/dies if not authorized)
require_permission('tickets.view');
```

### Assigning Roles

```php
use Karyalay\Services\RoleService;

// Assign a single role
RoleService::assignRole($userId, 'SUPPORT', $assignedByUserId);

// Set multiple roles (replaces existing)
RoleService::setUserRoles($userId, ['SUPPORT', 'SALES'], $assignedByUserId);

// Remove a role
RoleService::removeRole($userId, 'SALES');
```

## SALES_MANAGER Restrictions

The SALES_MANAGER role has special restrictions:
- Can view the Customers list page
- Can view individual Customer detail pages
- CANNOT click through to view Subscription, Order, Invoice, or Ticket details from the customer page
- The "View" buttons for these items are hidden for SALES_MANAGER users

## Migration Status

The migration has been successfully run and tested:

1. **user_roles table created** - Supports multiple roles per user with `user_id`, `role`, `assigned_by`, and `assigned_at` columns
2. **users.role ENUM updated** - Added new roles: SUPPORT, INFRASTRUCTURE, SALES, SALES_MANAGER, OPERATIONS, CONTENT_MANAGER
3. **Existing users migrated** - All existing users have been migrated to the user_roles table with their current role + CUSTOMER role

### Testing Completed

The following RoleService functions have been tested and verified working:
- `getUserRoles()` - Returns correct roles for a user
- `getUserPermissions()` - Returns all permissions based on user's roles (75 for ADMIN)
- `userHasPermission()` - Correctly checks if user has a specific permission
- `canAccessAdmin()` - Correctly identifies if user can access admin panel
- `assignRole()` - Successfully adds roles to users
- `removeRole()` - Successfully removes roles from users
- `setUserRoles()` - Sets multiple roles at once (always includes CUSTOMER)

## Notes

1. All users automatically have the CUSTOMER role in addition to any other assigned roles
2. The CUSTOMER role cannot be removed
3. The sidebar menu dynamically shows/hides items based on user permissions
4. The primary role (stored in `users.role`) is the first non-CUSTOMER role assigned
5. The `has_role()` function in `template_helpers.php` is wrapped with `function_exists()` to avoid conflicts with `admin_helpers.php`
6. The `hasRole()` function in `includes/auth_helpers.php` uses RoleService to check the `user_roles` table for multi-role support
7. Users with admin panel access (any role except CUSTOMER-only) can navigate between customer portal and admin panel via the user dropdown menu
8. Users cannot edit their own roles - this prevents accidental self-lockout and requires another administrator to change roles
