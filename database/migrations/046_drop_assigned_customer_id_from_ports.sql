-- Migration 046: Drop assigned_customer_id from ports table
-- Port is assigned to subscription, not directly to customer
-- Customer relationship is derived through subscription

-- Drop the foreign key constraint first
ALTER TABLE ports DROP FOREIGN KEY IF EXISTS ports_ibfk_2;

-- Drop the column
ALTER TABLE ports DROP COLUMN IF EXISTS assigned_customer_id;

-- Also update port_allocation_logs to remove customer_id dependency
-- The customer can be derived from subscription when needed
