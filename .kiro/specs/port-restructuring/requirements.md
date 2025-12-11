# Requirements Document

## Introduction

This document specifies the requirements for restructuring the port management system. The current implementation ties ports to specific plans, limiting flexibility. The new system will decouple ports from plans and introduce resource limits (number of users and storage) directly on ports. This allows any port to be allocated to any plan based on availability, with the port's resource limits determining what the customer can use.

## Glossary

- **Port**: A deployable instance configuration containing connection details, resource limits, and assignment status
- **Plan**: A subscription tier that customers purchase (e.g., Basic, Professional, Enterprise)
- **Subscription**: A customer's active purchase of a plan
- **Resource Limits**: Constraints on port usage including maximum users and storage capacity
- **Port Allocation**: The process of assigning an available port to a customer's subscription
- **Allowed Users**: The maximum number of user accounts permitted on a port
- **Allowed Storage**: The maximum storage capacity in gigabytes (GB) permitted on a port

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to define resource limits on ports, so that I can control how much capacity each port provides regardless of the plan it serves.

#### Acceptance Criteria

1. WHEN an administrator creates a new port THEN the system SHALL require input for allowed_users (integer) and allowed_storage_gb (decimal) fields
2. WHEN an administrator edits an existing port THEN the system SHALL allow modification of allowed_users and allowed_storage_gb values
3. WHEN displaying port details THEN the system SHALL show the allowed_users and allowed_storage_gb values
4. IF an administrator attempts to save a port with allowed_users less than 1 THEN the system SHALL reject the input and display a validation error
5. IF an administrator attempts to save a port with allowed_storage_gb less than 0.1 THEN the system SHALL reject the input and display a validation error

### Requirement 2

**User Story:** As an administrator, I want ports to be independent of plans, so that any available port can be allocated to any subscription.

#### Acceptance Criteria

1. WHEN an administrator creates a new port THEN the system SHALL NOT require a plan_id field
2. WHEN an administrator edits a port THEN the system SHALL NOT display a plan association field
3. WHEN the system stores port data THEN the system SHALL NOT include a plan_id foreign key reference
4. WHEN displaying the port list THEN the system SHALL NOT show a plan column

### Requirement 3

**User Story:** As an administrator, I want to view and manage ports with the new resource limit fields, so that I can effectively manage the port pool.

#### Acceptance Criteria

1. WHEN displaying the port list THEN the system SHALL show columns for allowed_users and allowed_storage_gb
2. WHEN filtering ports THEN the system SHALL allow filtering by minimum allowed_users value
3. WHEN filtering ports THEN the system SHALL allow filtering by minimum allowed_storage_gb value
4. WHEN importing ports via CSV THEN the system SHALL accept allowed_users and allowed_storage_gb columns
5. WHEN importing ports via CSV THEN the system SHALL validate that allowed_users and allowed_storage_gb meet minimum requirements

### Requirement 4

**User Story:** As a customer, I want to purchase a plan and receive an available port, so that I can start using the service without delays.

#### Acceptance Criteria

1. WHEN a customer initiates checkout for a plan THEN the system SHALL check for any available port (status = 'AVAILABLE')
2. IF no available ports exist THEN the system SHALL prevent checkout and display a message indicating unavailability
3. WHEN a successful payment is processed THEN the system SHALL allocate the first available port to the subscription
4. WHEN a port is allocated THEN the system SHALL update the port status to 'ASSIGNED' and link it to the subscription

### Requirement 5

**User Story:** As a customer, I want to see my port's resource limits, so that I understand the capacity available to me.

#### Acceptance Criteria

1. WHEN a customer views their subscription details THEN the system SHALL display the assigned port's allowed_users value
2. WHEN a customer views their subscription details THEN the system SHALL display the assigned port's allowed_storage_gb value
3. WHEN a customer views their "My Port" page THEN the system SHALL show the resource limits alongside other port details

### Requirement 6

**User Story:** As a system administrator, I want the database schema updated to support the new port structure, so that the application can persist and query the new data correctly.

#### Acceptance Criteria

1. WHEN the migration runs THEN the system SHALL add an allowed_users column of type INT with a default value of 1
2. WHEN the migration runs THEN the system SHALL add an allowed_storage_gb column of type DECIMAL(10,2) with a default value of 1.00
3. WHEN the migration runs THEN the system SHALL remove the plan_id foreign key constraint from the ports table
4. WHEN the migration runs THEN the system SHALL drop the plan_id column from the ports table
5. WHEN the migration runs THEN the system SHALL preserve all existing port data during the schema change

