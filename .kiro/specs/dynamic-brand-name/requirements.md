# Requirements Document

## Introduction

This feature replaces all hardcoded "Karyalay" brand name references throughout the website with dynamic values fetched from the admin settings panel. The system already has a `site_name` setting in the database and general settings page, but templates currently display hardcoded brand names. This change will allow administrators to customize the brand name displayed across all public pages, admin panels, and customer portals without code modifications.

## Glossary

- **Brand Name**: The display name of the business/website shown in headers, footers, page titles, and other UI elements
- **Setting Model**: The PHP class (`Karyalay\Models\Setting`) that handles reading and writing configuration values from the `settings` database table
- **Template**: PHP files in the `templates/` directory that render common UI components (header, footer, etc.)
- **site_name**: The existing setting key in the database that stores the brand name value

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to change the brand name displayed across the entire website from the admin settings panel, so that I can customize the site identity without modifying code.

#### Acceptance Criteria

1. WHEN an administrator updates the site_name setting in the admin panel THEN the System SHALL persist the new brand name value to the database
2. WHEN the site_name setting is empty or not set THEN the System SHALL display a default fallback value of "Portal"
3. WHEN the site_name setting is updated THEN the System SHALL reflect the change on all pages without requiring a server restart

### Requirement 2

**User Story:** As a website visitor, I want to see the configured brand name in the public website header and footer, so that I experience consistent branding throughout the site.

#### Acceptance Criteria

1. WHEN a visitor views any public page THEN the System SHALL display the configured brand name in the site logo area
2. WHEN a visitor views any public page THEN the System SHALL display the configured brand name in the page title (browser tab)
3. WHEN a visitor views any public page THEN the System SHALL display the configured brand name in the footer copyright text
4. WHEN a visitor views any public page THEN the System SHALL display the configured brand name in the meta description when no custom description is provided

### Requirement 3

**User Story:** As an administrator, I want to see the configured brand name in the admin panel header and footer, so that the admin interface reflects the current branding.

#### Acceptance Criteria

1. WHEN an administrator views any admin page THEN the System SHALL display the configured brand name in the admin sidebar logo
2. WHEN an administrator views any admin page THEN the System SHALL display the configured brand name in the page title (browser tab)
3. WHEN an administrator views any admin page THEN the System SHALL display the configured brand name in the admin footer copyright text

### Requirement 4

**User Story:** As a customer, I want to see the configured brand name in the customer portal header and footer, so that I experience consistent branding in my account area.

#### Acceptance Criteria

1. WHEN a customer views any customer portal page THEN the System SHALL display the configured brand name in the portal sidebar logo
2. WHEN a customer views any customer portal page THEN the System SHALL display the configured brand name in the page title (browser tab)
3. WHEN a customer views any customer portal page THEN the System SHALL display the configured brand name in the portal footer copyright text

### Requirement 5

**User Story:** As a developer, I want a centralized helper function to retrieve the brand name, so that all templates use a consistent method for accessing the setting.

#### Acceptance Criteria

1. WHEN a template needs to display the brand name THEN the System SHALL provide a helper function that retrieves the site_name setting
2. WHEN the helper function is called multiple times on the same page THEN the System SHALL cache the value to avoid redundant database queries
3. WHEN the helper function is called THEN the System SHALL return the configured brand name or the default fallback value

### Requirement 6

**User Story:** As a developer, I want the CTA form template to use the dynamic brand name, so that marketing content reflects the current branding.

#### Acceptance Criteria

1. WHEN the CTA form template is rendered THEN the System SHALL use the configured brand name in the default subtitle text
