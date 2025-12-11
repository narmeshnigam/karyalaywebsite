-- Migration: Drop price column from plans table
-- The system now uses only mrp and discounted_price fields

-- First, ensure mrp and discounted_price are populated for existing plans
-- Copy price to mrp where mrp is NULL
UPDATE plans SET mrp = price WHERE mrp IS NULL OR mrp = 0;

-- Make mrp NOT NULL since it's now the primary price field
ALTER TABLE plans MODIFY COLUMN mrp DECIMAL(10, 2) NOT NULL COMMENT 'Maximum Retail Price';

-- Drop the legacy price column
ALTER TABLE plans DROP COLUMN price;
