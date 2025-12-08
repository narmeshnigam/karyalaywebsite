-- Migration: Update ports table - Remove port_number, Add database connection fields
-- This migration replaces port_number with database connection details

-- Drop the unique constraint that includes port_number
ALTER TABLE ports DROP INDEX unique_instance;

-- Remove port_number column
ALTER TABLE ports DROP COLUMN port_number;

-- Add new database connection columns
ALTER TABLE ports 
    ADD COLUMN db_host VARCHAR(255) AFTER instance_url,
    ADD COLUMN db_name VARCHAR(255) AFTER db_host,
    ADD COLUMN db_username VARCHAR(255) AFTER db_name,
    ADD COLUMN db_password VARCHAR(255) AFTER db_username;

-- Create new unique constraint on instance_url only
ALTER TABLE ports ADD UNIQUE KEY unique_instance (instance_url);

-- Add index for faster lookups
ALTER TABLE ports ADD INDEX idx_db_host (db_host);
