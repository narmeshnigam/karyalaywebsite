# Implementation Plan

- [x] 1. Create EnvironmentConfigManager class
  - [x] 1.1 Create the EnvironmentConfigManager class with constants for prefixes
    - Create `classes/Services/EnvironmentConfigManager.php`
    - Define LOCAL_PREFIX, LIVE_PREFIX, and ACTIVE_PREFIX constants
    - Implement constructor and basic structure
    - _Requirements: 8.2, 8.3_

  - [x] 1.2 Write property test for credential prefix correctness
    - **Property 4: Credential Prefix Correctness**
    - **Validates: Requirements 1.4, 8.2, 8.3**

  - [x] 1.3 Implement writeDualConfig method
    - Write method to save both local and live credentials to .env
    - Include section comments and clear organization
    - Handle null credentials for either environment
    - _Requirements: 8.1, 1.4_

  - [x] 1.4 Write property test for config file update safety
    - **Property 8: Config File Update Safety**
    - **Validates: Requirements 4.4**

  - [x] 1.5 Implement readEnvironmentCredentials method
    - Parse .env file and extract credentials by prefix
    - Return null if credentials not configured
    - _Requirements: 8.4_

  - [x] 1.6 Implement resolveCredentials method
    - Detect current environment using InstallationService
    - Apply credential resolution logic based on environment and availability
    - Return resolved credentials array
    - _Requirements: 2.2, 2.3, 2.4, 5.1, 5.3_

  - [x] 1.7 Write property test for credential resolution
    - **Property 3: Credential Resolution Based on Environment**
    - **Validates: Requirements 2.2, 2.3, 2.4, 5.1, 5.3, 8.4**

- [x] 2. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Extend InstallationService for dual-environment support
  - [x] 3.1 Add environment credential methods to InstallationService
    - Implement saveEnvironmentCredentials method
    - Implement getEnvironmentCredentials method
    - Implement hasEnvironmentCredentials method
    - _Requirements: 1.4, 4.3_

  - [x] 3.2 Write property test for credential validation before save
    - **Property 1: Credential Validation Before Save**
    - **Validates: Requirements 1.3, 4.3**

  - [x] 3.3 Implement getActiveEnvironment method
    - Use existing detectEnvironment method
    - Return 'local' or 'live' based on detection
    - _Requirements: 2.1_

  - [x] 3.4 Write property test for environment detection accuracy
    - **Property 2: Environment Detection Accuracy**
    - **Validates: Requirements 2.1**

  - [x] 3.5 Implement resolveActiveCredentials method
    - Integrate with EnvironmentConfigManager
    - Set active DB_ variables in environment
    - _Requirements: 8.4_

- [x] 4. Update database configuration step UI
  - [x] 4.1 Add environment selector radio buttons
    - Add radio button group for Localhost/Live selection
    - Add descriptions and tooltips for each option
    - Pre-select Localhost by default
    - _Requirements: 1.1, 3.1, 6.1_

  - [x] 4.2 Implement dual credential forms
    - Create separate form sections for local and live credentials
    - Add "Also configure Live credentials" checkbox
    - Show/hide forms based on selection
    - _Requirements: 1.2, 3.2, 3.3_

  - [x] 4.3 Implement form state persistence
    - Save entered credentials to session on environment switch
    - Restore credentials when switching back
    - _Requirements: 3.4_

  - [x] 4.4 Write property test for session data persistence
    - **Property 5: Session Data Persistence on Environment Switch**
    - **Validates: Requirements 3.4**

  - [x] 4.5 Update form submission handling
    - Validate selected environment credentials
    - Optionally validate secondary environment if configured
    - Save both credential sets using EnvironmentConfigManager
    - _Requirements: 1.3, 1.5_

  - [x] 4.6 Write property test for progression with valid credentials
    - **Property 9: Progression with Valid Credentials**
    - **Validates: Requirements 1.5**

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Update bootstrap and database configuration loading
  - [x] 6.1 Modify config/bootstrap.php for dual-environment support
    - Load EnvironmentConfigManager
    - Call resolveCredentials on application start
    - Set active DB_ environment variables
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 6.2 Update config/database.php to use resolved credentials
    - Ensure database config reads from active DB_ variables
    - No changes needed if already using getenv()
    - _Requirements: 8.4_

  - [x] 6.3 Add fallback to installation wizard
    - Redirect to install wizard if no valid credentials available
    - _Requirements: 2.5_

- [x] 7. Implement validation error handling
  - [x] 7.1 Create specific error messages for each validation failure
    - Host unreachable errors
    - Invalid credentials errors
    - Database not found errors
    - Socket errors
    - _Requirements: 6.3, 6.5_

  - [x] 7.2 Write property test for validation error message specificity
    - **Property 7: Validation Error Message Specificity**
    - **Validates: Requirements 6.3**

  - [x] 7.3 Implement success feedback
    - Display connection success message with details
    - Show server version and connection info
    - _Requirements: 6.4_

- [x] 8. Update URL handling
  - [x] 8.1 Create URL helper function for base URL resolution
    - Check APP_URL environment variable
    - Fall back to request detection
    - Respect detected protocol
    - _Requirements: 7.1, 7.2, 7.4_

  - [x] 8.2 Write property test for URL base resolution
    - **Property 6: URL Base Resolution**
    - **Validates: Requirements 7.1, 7.2, 7.4**

  - [x] 8.3 Update installation completion redirect
    - Use resolved base URL for admin dashboard redirect
    - _Requirements: 7.3_

- [ ] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Add admin settings for live credentials
  - [x] 10.1 Create admin database settings page
    - Add new page or section in admin settings
    - Display current environment and active credentials (masked)
    - _Requirements: 4.2_

  - [x] 10.2 Implement live credentials form in admin
    - Form to add/update live database credentials
    - Test connection before saving
    - _Requirements: 4.2, 4.3_

  - [x] 10.3 Implement credential update without disruption
    - Save new credentials without affecting current connection
    - Credentials take effect on next request
    - _Requirements: 4.4_

- [x] 11. Update .env template and documentation
  - [x] 11.1 Update .env.example with dual-environment structure
    - Add section comments explaining dual-environment setup
    - Include all DB_LOCAL_ and DB_LIVE_ variables
    - Add instructions for forcing localhost
    - _Requirements: 8.1, 8.5, 5.2_

  - [x] 11.2 Update installation documentation
    - Document dual-environment feature
    - Explain credential resolution logic
    - Provide examples for common scenarios
    - _Requirements: 6.1_

- [ ] 12. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

