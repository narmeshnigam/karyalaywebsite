# Implementation Plan

- [x] 1. Create SMTP Settings Admin Page
  - [x] 1.1 Create `admin/smtp-settings.php` with form layout matching payment-settings.php pattern
    - Include all SMTP fields: host, port, username, password, encryption, from_address, from_name
    - Add password visibility toggle functionality
    - Include CSRF token protection
    - _Requirements: 1.1, 1.2, 1.3, 6.1, 6.2, 6.3_
  - [x] 1.2 Implement form validation and settings persistence
    - Validate all required fields server-side
    - Save settings using Setting model's setMultiple method
    - Display success/error messages
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [ ]* 1.3 Write property test for settings round-trip persistence
    - **Property 1: Settings Round-Trip Persistence**
    - **Validates: Requirements 2.1, 4.4, 4.5**
  - [ ]* 1.4 Write property test for invalid input rejection
    - **Property 3: Invalid Input Rejection**
    - **Validates: Requirements 2.2**

- [x] 2. Create SMTP Connection Test API
  - [x] 2.1 Create `admin/api/test-smtp.php` endpoint
    - Accept SMTP credentials via POST
    - Attempt PHPMailer SMTP connection
    - Return JSON success/error response
    - _Requirements: 3.1, 3.2, 3.3_
  - [x] 2.2 Add test connection button and JavaScript handler to smtp-settings.php
    - Wire up AJAX call to test endpoint
    - Display test results in UI
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 3. Update EmailService to Read from Database
  - [x] 3.1 Add `loadSettingsFromDatabase()` method to EmailService
    - Query Setting model for SMTP keys
    - Return array of settings or null if not found
    - _Requirements: 4.1, 4.2, 4.3_
  - [x] 3.2 Modify `configure()` method to prioritize database settings
    - Check database first, fall back to environment variables
    - _Requirements: 4.1, 4.2, 4.3_
  - [ ]* 3.3 Write property test for database settings priority
    - **Property 4: Database Settings Priority**
    - **Validates: Requirements 4.2**
  - [ ]* 3.4 Write property test for form population from database
    - **Property 2: Form Population from Database**
    - **Validates: Requirements 1.2**

- [x] 4. Update Admin Navigation
  - [x] 4.1 Add "Integrations" section to admin-header.php
    - Add section header for "Integrations"
    - Add SMTP Settings link with appropriate icon
    - Add Payment Settings link (move from current location)
    - _Requirements: 5.1_
  - [x] 4.2 Implement active state highlighting for Integrations section
    - Detect current page for SMTP and Payment settings
    - Apply active class to correct navigation item
    - _Requirements: 5.2, 5.3_

- [x] 5. Security and CSRF Validation
  - [x] 5.1 Write property test for CSRF token validation
    - **Property 5: CSRF Token Validation**
    - **Validates: Requirements 6.3**

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
