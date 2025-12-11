# Requirements Document

## Introduction

This feature adds an SMTP Settings management page to the admin panel, allowing administrators to configure email server settings through a user-friendly interface instead of relying on hardcoded environment variables. The feature also integrates both SMTP and Payment settings into a unified "Integrations" section in the admin navigation menu.

Currently, SMTP settings are read from environment variables (`$_ENV['MAIL_HOST']`, `$_ENV['MAIL_USERNAME']`, etc.) in the `EmailService` class. This feature will enable administrators to manage these settings dynamically through the database, similar to how payment settings are already managed.

## Glossary

- **SMTP**: Simple Mail Transfer Protocol - the standard protocol for sending emails
- **Admin Panel**: The administrative interface accessible to users with ADMIN role
- **Setting Model**: The existing database model (`classes/Models/Setting.php`) that stores key-value configuration pairs
- **EmailService**: The existing service (`classes/Services/EmailService.php`) that handles email sending via PHPMailer
- **Integrations Section**: A new navigation section grouping external service configurations (SMTP, Payment Gateway)

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to access SMTP settings through the admin panel, so that I can configure email server settings without modifying code or environment files.

#### Acceptance Criteria

1. WHEN an administrator navigates to the SMTP settings page THEN the system SHALL display a form with all SMTP configuration fields (host, port, username, password, encryption, from address, from name)
2. WHEN the SMTP settings page loads THEN the system SHALL populate form fields with current saved values from the database
3. WHEN no SMTP settings exist in the database THEN the system SHALL display empty form fields with appropriate placeholder text

### Requirement 2

**User Story:** As an administrator, I want to save SMTP configuration settings, so that the email system uses my specified mail server.

#### Acceptance Criteria

1. WHEN an administrator submits valid SMTP settings THEN the system SHALL persist all settings to the database using the Setting model
2. WHEN an administrator submits the form without required fields THEN the system SHALL display validation error messages and prevent submission
3. WHEN SMTP settings are saved successfully THEN the system SHALL display a success confirmation message
4. WHEN saving SMTP settings fails THEN the system SHALL display an error message and retain the form data

### Requirement 3

**User Story:** As an administrator, I want to test SMTP connection before saving, so that I can verify the settings work correctly.

#### Acceptance Criteria

1. WHEN an administrator clicks the test connection button THEN the system SHALL attempt to establish an SMTP connection using the provided credentials
2. WHEN the SMTP connection test succeeds THEN the system SHALL display a success message indicating the connection is valid
3. WHEN the SMTP connection test fails THEN the system SHALL display an error message with details about the failure

### Requirement 4

**User Story:** As a system component, I want the EmailService to read SMTP settings from the database, so that administrator-configured settings are used for sending emails.

#### Acceptance Criteria

1. WHEN the EmailService initializes THEN the system SHALL first attempt to load SMTP settings from the database
2. WHEN database SMTP settings exist THEN the system SHALL use those settings for email configuration
3. WHEN database SMTP settings do not exist THEN the system SHALL fall back to environment variables
4. WHEN serializing SMTP settings to the database THEN the system SHALL store them as key-value pairs
5. WHEN deserializing SMTP settings from the database THEN the system SHALL reconstruct the configuration correctly

### Requirement 5

**User Story:** As an administrator, I want SMTP and Payment settings grouped together in the navigation, so that I can easily find all external service configurations.

#### Acceptance Criteria

1. WHEN an administrator views the admin sidebar THEN the system SHALL display an "Integrations" section containing SMTP Settings and Payment Settings links
2. WHEN an administrator is on the SMTP settings page THEN the system SHALL highlight the SMTP Settings link as active
3. WHEN an administrator is on the Payment settings page THEN the system SHALL highlight the Payment Settings link as active

### Requirement 6

**User Story:** As an administrator, I want SMTP credentials to be handled securely, so that sensitive information is protected.

#### Acceptance Criteria

1. WHEN displaying the SMTP password field THEN the system SHALL mask the password value by default
2. WHEN an administrator clicks the show/hide toggle THEN the system SHALL toggle password visibility
3. WHEN SMTP settings are submitted THEN the system SHALL validate the CSRF token before processing
