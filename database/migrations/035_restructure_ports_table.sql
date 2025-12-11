-- Migration 035: Restructure ports table
-- This migration decouples ports from plans
-- 
-- Changes:
-- 1. Drop plan_id foreign key constraint
-- 2. Drop idx_plan_id index
-- 3. Drop plan_id column

-- Step 1: Drop the foreign key constraint on plan_id
-- Note: The constraint name from migration 006 is the default MySQL naming
ALTER TABLE ports DROP FOREIGN KEY ports_ibfk_1;

-- Step 2: Drop the index on plan_id
DROP INDEX idx_plan_id ON ports;

-- Step 3: Drop the plan_id column
ALTER TABLE ports DROP COLUMN plan_id;
