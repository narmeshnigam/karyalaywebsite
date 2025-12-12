# Requirements Document

## Introduction

This feature enhances the installation wizard to support dual-environment configuration (localhost and live/production). The system allows users to configure database credentials for both environments during initial setup, with intelligent environment detection and automatic credential switching. The goal is to enable a seamless "git push and go live" workflow where the same codebase works on both localhost development and production hosting (e.g., Hostinger) without manual configuration file changes.

## Glossary

- **Installation_Wizard**: The web-based setup interface that guides users through initial system configuration
- **Environment_Selector**: A UI component (radio button group) that allows users to choose between localhost and live environment configurations
- **Localhost_Environment**: A local development environment (XAMPP, MAMP, WAMP, or similar) typically running on 127.0.0.1 or localhost
- **Live_Environment**: A production hosting environment (e.g., Hostinger shared hosting, VPS) accessible via public domain
- **Credential_Set**: A collection of database connection parameters (host, port, database name, username, password, unix_socket)
- **Environment_Config_File**: The .env file that stores environment-specific configuration values
- **Active_Environment**: The currently selected environment whose credentials are used for database connections
- **Fallback_Mechanism**: Logic that automatically uses localhost credentials when live credentials are unavailable

## Requirements

### Requirement 1

**User Story:** As a developer, I want to configure both localhost and live database credentials during initial setup, so that I can deploy the same codebase to production without manual configuration changes.

#### Acceptance Criteria

1. WHEN the Installation_Wizard loads the database configuration step THEN the system SHALL display an Environment_Selector with two options: "Localhost" and "Live"
2. WHEN a user selects an environment option THEN the system SHALL display the corresponding credential input form for that environment
3. WHEN a user submits database credentials for an environment THEN the system SHALL validate and test the connection before saving
4. WHEN credentials are saved THEN the system SHALL store both Credential_Sets in the Environment_Config_File with distinct prefixes (DB_ for active, DB_LOCAL_ for localhost, DB_LIVE_ for live)
5. WHEN the user completes the database step with at least one valid Credential_Set THEN the system SHALL allow progression to the next installation step

### Requirement 2

**User Story:** As a system administrator, I want the application to automatically detect and use the appropriate database credentials based on the current environment, so that the system works correctly without manual intervention.

#### Acceptance Criteria

1. WHEN the application starts THEN the system SHALL detect whether it is running in Localhost_Environment or Live_Environment
2. WHEN running in Live_Environment with valid live credentials THEN the system SHALL use the DB_LIVE_ Credential_Set
3. WHEN running in Localhost_Environment THEN the system SHALL use the DB_LOCAL_ Credential_Set
4. WHEN the preferred Credential_Set is unavailable or invalid THEN the system SHALL fall back to the alternative Credential_Set
5. WHEN neither Credential_Set is available THEN the system SHALL redirect to the Installation_Wizard

### Requirement 3

**User Story:** As a developer, I want localhost credentials to be pre-populated by default during fresh installation, so that I can quickly set up my local development environment.

#### Acceptance Criteria

1. WHEN the Installation_Wizard starts on a fresh installation THEN the system SHALL pre-select the "Localhost" environment option
2. WHEN the "Localhost" option is selected THEN the system SHALL pre-populate the host field with "localhost" and port with "3306"
3. WHEN the user switches to "Live" environment THEN the system SHALL clear pre-populated values and show empty fields
4. WHEN the user switches back to "Localhost" THEN the system SHALL restore any previously entered localhost credentials from session

### Requirement 4

**User Story:** As a system administrator, I want to configure live credentials after initial localhost setup, so that I can prepare the system for production deployment.

#### Acceptance Criteria

1. WHEN the system is installed with only localhost credentials THEN the system SHALL function normally in Localhost_Environment
2. WHEN an administrator accesses the admin settings THEN the system SHALL provide an option to configure live database credentials
3. WHEN live credentials are added post-installation THEN the system SHALL validate and test the connection before saving
4. WHEN valid live credentials are saved THEN the system SHALL update the Environment_Config_File without disrupting current operations

### Requirement 5

**User Story:** As a developer, I want to force the application to use localhost credentials even when live credentials are available, so that I can test locally without affecting production data.

#### Acceptance Criteria

1. WHEN both Credential_Sets are configured THEN the system SHALL prefer Live_Environment credentials by default on production servers
2. WHEN a developer needs to use localhost credentials on a system with both configured THEN the developer SHALL be able to comment out or disable live credentials in the Environment_Config_File
3. WHEN live credentials are commented out or empty THEN the system SHALL automatically use localhost credentials regardless of detected environment

### Requirement 6

**User Story:** As a first-time user, I want a clear and intuitive setup experience, so that I can configure the system without technical expertise.

#### Acceptance Criteria

1. WHEN the Environment_Selector is displayed THEN the system SHALL show clear labels and descriptions for each environment option
2. WHEN a user hovers over or focuses on an environment option THEN the system SHALL display helpful tooltip text explaining the option
3. WHEN validation errors occur THEN the system SHALL display specific, actionable error messages near the relevant input fields
4. WHEN the connection test succeeds THEN the system SHALL display a success message with connection details
5. WHEN the connection test fails THEN the system SHALL display the error reason and suggest troubleshooting steps

### Requirement 7

**User Story:** As a system administrator, I want the URL routing to work consistently across both environments, so that all links and redirects function correctly.

#### Acceptance Criteria

1. WHEN the application generates URLs THEN the system SHALL use the APP_URL environment variable as the base
2. WHEN APP_URL is not configured THEN the system SHALL detect and use the current request's host and protocol
3. WHEN redirecting after installation completion THEN the system SHALL redirect to the correct admin dashboard URL for the current environment
4. WHEN the system detects a protocol mismatch (HTTP vs HTTPS) THEN the system SHALL use the detected protocol rather than forcing a specific one

### Requirement 8

**User Story:** As a developer, I want the .env file structure to clearly separate localhost and live credentials, so that I can easily understand and modify the configuration.

#### Acceptance Criteria

1. WHEN the Installation_Wizard writes credentials THEN the system SHALL organize the Environment_Config_File with clearly labeled sections
2. WHEN writing localhost credentials THEN the system SHALL use the DB_LOCAL_ prefix for all database variables
3. WHEN writing live credentials THEN the system SHALL use the DB_LIVE_ prefix for all database variables
4. WHEN the system reads credentials THEN the system SHALL populate the active DB_ variables from the appropriate prefixed set based on detected environment
5. WHEN the Environment_Config_File is read THEN the system SHALL include comments explaining the dual-environment configuration

