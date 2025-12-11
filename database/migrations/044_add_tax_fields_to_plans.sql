-- Migration: Add tax handling fields to plans table
-- Fields: discount_amount, net_price, tax_percent, tax_name, tax_description, tax_amount
-- Logic: net_price + tax_amount = selling_price (discounted_price or mrp)

-- Add tax and discount breakdown fields
ALTER TABLE plans
    ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT NULL COMMENT 'Discount amount (mrp - discounted_price)',
    ADD COLUMN net_price DECIMAL(10, 2) DEFAULT NULL COMMENT 'Price before tax (selling_price - tax_amount)',
    ADD COLUMN tax_percent DECIMAL(5, 2) DEFAULT NULL COMMENT 'Tax percentage (e.g., 18.00 for 18%)',
    ADD COLUMN tax_name VARCHAR(100) DEFAULT NULL COMMENT 'Tax name (e.g., GST, VAT, Sales Tax)',
    ADD COLUMN tax_description VARCHAR(255) DEFAULT NULL COMMENT 'Tax description for invoice',
    ADD COLUMN tax_amount DECIMAL(10, 2) DEFAULT NULL COMMENT 'Tax amount (calculated from net_price * tax_percent / 100)';

-- Add index for tax-related queries
ALTER TABLE plans ADD INDEX idx_tax_percent (tax_percent);
