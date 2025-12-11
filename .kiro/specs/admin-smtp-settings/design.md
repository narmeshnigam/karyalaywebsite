# Design Document: Admin SMTP Settings

## Overview

This feature adds an SMTP Settings management page to the admin panel, enabling administrators to configure email server settings through a database-backed interface. The implementation follows the existing patterns established by the Payment Settings page (`admin/payment-settings.php`) and integrates with the existing `Setting` model for persistence.

The feature also reorganizes the admin navigation to group SMTP and Payment settings under a new "Integrations" section, improving discoverability of external service configurations.

## Architecture

The feature follows the existing MVC-like architecture of the application:

```
┌─────────────────────────────────────────────────────────────────┐
│                        Admin Panel                               │
├─────────────────────────────────────────────────────────────────┤
│  admin/smtp-settings.php     │  admin/payment-settings.php      │
│  (New SMTP Settings Page)    │  (Existing Payment Settings)     │
└──────────────┬───────────────┴──────────────┬───────────────────┘
               │                              │
               ▼                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    classes/Models/Setting.php                    │
│                    (Existing Setting Model)                      │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                    classes/Services/EmailService.php             │
│                    (Modified to read from DB)                    │
└─────────────────────────────────────────────────────────────────┘
```

### Data Flow

1. Admin navigates to SMTP Settings page
2. Page loads current settings from database via `Setting` model
3. Admin modifies settings and submits form
4. Settings are validated and saved to database
5. `EmailService` reads settings from database on initialization

## Components and Interfaces

### 1. SMTP Settings Admin Page (`admin/smtp-settings.php`)

New admin page following the pattern of `admin/payment-settings.php`.

**Responsibilities:**
- Display SMTP configuration form
- Validate form input
- Save settings to database
- Handle SMTP connection testing

**Form Fields:**
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| smtp_host | text | Yes | Non-empty string |
| smtp_port | number | Yes | Integer 1-65535 |
| smtp_username | text | Yes | Non-empty string |
| smtp_password | password | Yes | Non-empty string |
| smtp_encryption | select | Yes | One of: tls, ssl, none |
| smtp_from_address | email | Yes | Valid email format |
| smtp_from_name | text | Yes | Non-empty string |

### 2. SMTP Test API Endpoint (`admin/api/test-smtp.php`)

API endpoint for testing SMTP connection without saving.

**Request:**
```php
POST /admin/api/test-smtp.php
Content-Type: application/x-www-form-urlencoded

csrf_token=xxx&smtp_host=xxx&smtp_port=587&smtp_username=xxx&smtp_password=xxx&smtp_encryption=tls
```

**Response:**
```json
{
  "success": true|false,
  "message": "Connection successful" | "Error details"
}
```

### 3. Modified EmailService (`classes/Services/EmailService.php`)

Updated to prioritize database settings over environment variables.

**Configuration Priority:**
1. Database settings (via `Setting` model)
2. Environment variables (fallback)
3. Default values (last resort)

**New Method:**
```php
private function loadSettingsFromDatabase(): array
```

### 4. Admin Navigation Update (`templates/admin-header.php`)

Add "Integrations" section with SMTP and Payment settings links.

## Data Models

### Settings Table Schema (Existing)

The feature uses the existing `settings` table:

```sql
CREATE TABLE settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### SMTP Settings Keys

| Key | Description | Example Value |
|-----|-------------|---------------|
| smtp_host | SMTP server hostname | smtp.gmail.com |
| smtp_port | SMTP server port | 587 |
| smtp_username | SMTP authentication username | user@example.com |
| smtp_password | SMTP authentication password | (encrypted) |
| smtp_encryption | Encryption type | tls |
| smtp_from_address | Sender email address | noreply@example.com |
| smtp_from_name | Sender display name | My Application |

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Settings Round-Trip Persistence
*For any* valid SMTP settings configuration, saving the settings to the database and then retrieving them should return an equivalent configuration with all values preserved.
**Validates: Requirements 2.1, 4.4, 4.5**

### Property 2: Form Population from Database
*For any* set of SMTP settings stored in the database, loading the SMTP settings page should populate all form fields with the corresponding stored values.
**Validates: Requirements 1.2**

### Property 3: Invalid Input Rejection
*For any* form submission missing one or more required fields (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_from_address, smtp_from_name), the system should reject the submission and not modify the database.
**Validates: Requirements 2.2**

### Property 4: Database Settings Priority
*For any* EmailService initialization where database SMTP settings exist, the service should use the database values rather than environment variables for configuration.
**Validates: Requirements 4.2**

### Property 5: CSRF Token Validation
*For any* form submission to the SMTP settings page, the system should reject requests with missing or invalid CSRF tokens and not modify the database.
**Validates: Requirements 6.3**

## Error Handling

### Form Validation Errors
- Display inline error messages next to invalid fields
- Preserve user input on validation failure
- Prevent form submission until errors are corrected

### Database Errors
- Log detailed error information
- Display user-friendly error message
- Maintain form state for retry

### SMTP Connection Test Errors
- Capture PHPMailer exception details
- Display specific error message (connection refused, authentication failed, etc.)
- Do not save settings on test failure

## Testing Strategy

### Property-Based Testing

The implementation will use PHPUnit with data providers for property-based testing. Each correctness property will be implemented as a dedicated test class.

**Testing Framework:** PHPUnit (already configured in the project)

**Property Test Requirements:**
- Each property test must run a minimum of 100 iterations with varied inputs
- Tests must be tagged with the format: `**Feature: admin-smtp-settings, Property {number}: {property_text}**`
- Generators should produce valid SMTP configurations with randomized values

### Unit Tests

Unit tests will cover:
- Setting model SMTP-specific operations
- EmailService configuration loading logic
- Form validation functions
- CSRF token validation

### Test Data Generators

```php
// Example generator for valid SMTP settings
function generateValidSmtpSettings(): array {
    return [
        'smtp_host' => 'smtp.' . randomDomain(),
        'smtp_port' => randomElement([25, 465, 587, 2525]),
        'smtp_username' => randomEmail(),
        'smtp_password' => randomString(16),
        'smtp_encryption' => randomElement(['tls', 'ssl', 'none']),
        'smtp_from_address' => randomEmail(),
        'smtp_from_name' => randomString(20)
    ];
}
```

### Integration Tests

- End-to-end form submission and retrieval
- EmailService initialization with database settings
- Navigation highlighting verification
