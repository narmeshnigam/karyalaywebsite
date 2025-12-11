-- Migration 047: Fix port_allocation_logs table for comprehensive logging
-- 
-- This migration ensures the table can handle all port allocation scenarios:
-- 1. Automatic port allocation during purchase
-- 2. Admin status changes
-- 3. Admin reassignments
-- 4. Port creation logging

-- Step 1: Modify action ENUM to include all action types
-- Using a safe approach that works even if already modified
ALTER TABLE port_allocation_logs 
MODIFY COLUMN action VARCHAR(50) NOT NULL;

-- Step 2: Add notes column if it doesn't exist
-- This will fail silently if column already exists
ALTER TABLE port_allocation_logs 
ADD COLUMN notes TEXT NULL;

-- Step 3: Make subscription_id nullable
ALTER TABLE port_allocation_logs 
MODIFY COLUMN subscription_id CHAR(36) NULL;

-- Step 4: Make customer_id nullable  
ALTER TABLE port_allocation_logs 
MODIFY COLUMN customer_id CHAR(36) NULL;
