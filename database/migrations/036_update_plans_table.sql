-- Migration: Update plans table for enhanced plan management
-- Adds: number_of_users, allowed_storage, mrp, discounted_price, features_html

-- Add new columns to plans table
ALTER TABLE plans
    ADD COLUMN number_of_users INT DEFAULT NULL COMMENT 'Maximum number of users allowed',
    ADD COLUMN allowed_storage_gb DECIMAL(10, 2) DEFAULT NULL COMMENT 'Allowed storage in GB',
    ADD COLUMN mrp DECIMAL(10, 2) DEFAULT NULL COMMENT 'Maximum Retail Price (original price)',
    ADD COLUMN discounted_price DECIMAL(10, 2) DEFAULT NULL COMMENT 'Discounted/sale price',
    ADD COLUMN features_html TEXT DEFAULT NULL COMMENT 'Rich text HTML features description';

-- Update price column to be nullable (will use mrp/discounted_price instead)
-- Keep price for backward compatibility, it will represent the final price

-- Add index for billing period to support duration filtering
ALTER TABLE plans ADD INDEX idx_billing_period (billing_period_months);

-- Add index for status and billing period combination
ALTER TABLE plans ADD INDEX idx_status_billing (status, billing_period_months);
