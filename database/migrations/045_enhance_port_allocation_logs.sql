-- Migration 045: Enhance port_allocation_logs table
-- 
-- Changes:
-- 1. Expand action ENUM to include more status types
-- 2. Add notes column for additional context
-- 3. Make subscription_id and customer_id nullable for non-assignment actions

-- Step 1: Modify action ENUM to include more types
ALTER TABLE port_allocation_logs 
MODIFY COLUMN action ENUM('ASSIGNED', 'REASSIGNED', 'RELEASED', 'UNASSIGNED', 'CREATED', 'DISABLED', 'ENABLED', 'RESERVED', 'MADE_AVAILABLE', 'STATUS_CHANGED') NOT NULL;

-- Step 2: Add notes column
ALTER TABLE port_allocation_logs 
ADD COLUMN notes TEXT NULL AFTER performed_by;

-- Step 3: Make subscription_id nullable (for non-assignment actions like CREATED)
ALTER TABLE port_allocation_logs 
MODIFY COLUMN subscription_id CHAR(36) NULL;

-- Step 4: Make customer_id nullable (for non-assignment actions like CREATED)
ALTER TABLE port_allocation_logs 
MODIFY COLUMN customer_id CHAR(36) NULL;

-- Step 5: Drop existing foreign key constraints that require NOT NULL
ALTER TABLE port_allocation_logs DROP FOREIGN KEY port_allocation_logs_ibfk_2;
ALTER TABLE port_allocation_logs DROP FOREIGN KEY port_allocation_logs_ibfk_3;

-- Step 6: Re-add foreign keys with ON DELETE SET NULL
ALTER TABLE port_allocation_logs 
ADD CONSTRAINT fk_pal_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL;

ALTER TABLE port_allocation_logs 
ADD CONSTRAINT fk_pal_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL;
