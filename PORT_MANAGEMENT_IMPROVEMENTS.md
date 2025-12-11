# Port Management Improvements

## Overview

This update improves the port details edit page with better organization, clearer assignment handling, and comprehensive allocation logging.

## Key Changes

### 1. Reorganized Port Edit Page (`admin/ports/edit.php`)

The edit page now has organized sections:

- **Port Information**: Basic details, status, region, notes
- **Database Configuration**: Connection credentials
- **Setup Instructions**: Rich text editor for customer-facing instructions
- **Port Assignment** (sidebar): Shows current assignment status with customer/subscription details
- **Subscription Link** (sidebar): Shows if any subscription references this port
- **Allocation History**: Complete log of all port activities

### 2. Assignment Inconsistency Detection

The system now detects and alerts admins when:
- A port has status other than "ASSIGNED" but a subscription still links to it
- A port has status "ASSIGNED" but no subscription links to it

This helps identify cases where a port was unassigned but the subscription still references it.

### 3. Unlink Subscription Feature

Admins can now explicitly unlink a subscription from a port without changing the port status. This is useful when:
- A port needs to be made available but the subscription link wasn't properly cleared
- Cleaning up orphaned references

### 4. Enhanced Allocation Logging

The `port_allocation_logs` table now supports more action types:

| Action | Description |
|--------|-------------|
| ASSIGNED | Port assigned to subscription (automatic or manual) |
| REASSIGNED | Port reassigned to different customer |
| RELEASED | Port released and made available |
| UNASSIGNED | Port unassigned but subscription may still reference it |
| CREATED | Port was created |
| DISABLED | Port status changed to DISABLED |
| ENABLED | Port status changed from DISABLED |
| RESERVED | Port status changed to RESERVED |
| MADE_AVAILABLE | Port status changed to AVAILABLE |
| STATUS_CHANGED | Generic status change |

Logs now also include:
- `notes` field for additional context
- Nullable `subscription_id` and `customer_id` for non-assignment actions

### 5. Updated View Page (`admin/ports/view.php`)

The view page now shows:
- Both port assignment status AND subscription link status side by side
- Allocation history with all activities
- Inconsistency warnings when detected

## Database Migration

Run migration 045 to update the `port_allocation_logs` table:

```bash
php database/run_migration_045.php
```

This migration:
1. Expands the `action` ENUM to include new action types
2. Adds `notes` column
3. Makes `subscription_id` and `customer_id` nullable

## How Assignment Works

### Port Record (`ports` table)
- `assigned_subscription_id`: Subscription the port is assigned to (customer is derived from subscription)
- `assigned_at`: When assignment occurred
- `status`: Current status (AVAILABLE, RESERVED, ASSIGNED, DISABLED)

Note: The `assigned_customer_id` column has been removed. Customer information is now derived through the subscription relationship.

### Subscription Record (`subscriptions` table)
- `assigned_port_id`: Port linked to this subscription

### Consistency Rules
- When a port is ASSIGNED, both the port record AND subscription record should reference each other
- When unassigning, both records should be updated
- The new UI helps identify and fix inconsistencies

## Usage

### Assigning a Port
1. Ports are automatically assigned during checkout when payment succeeds
2. Admins can reassign ports via the edit page

### Unassigning a Port
1. Change port status from ASSIGNED to another status
2. The system will automatically clear the subscription link
3. A log entry is created

### Fixing Inconsistencies
1. View the port edit page
2. If an inconsistency is detected, a warning banner appears
3. Use "Unlink Subscription" button or change status as needed
