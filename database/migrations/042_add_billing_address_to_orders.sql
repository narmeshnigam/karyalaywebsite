-- Add billing address columns to orders table
ALTER TABLE orders 
ADD COLUMN billing_full_name VARCHAR(255) AFTER payment_method,
ADD COLUMN billing_business_name VARCHAR(255) AFTER billing_full_name,
ADD COLUMN billing_business_tax_id VARCHAR(100) AFTER billing_business_name,
ADD COLUMN billing_address_line1 VARCHAR(255) AFTER billing_business_tax_id,
ADD COLUMN billing_address_line2 VARCHAR(255) AFTER billing_address_line1,
ADD COLUMN billing_city VARCHAR(100) AFTER billing_address_line2,
ADD COLUMN billing_state VARCHAR(100) AFTER billing_city,
ADD COLUMN billing_postal_code VARCHAR(20) AFTER billing_state,
ADD COLUMN billing_country VARCHAR(100) AFTER billing_postal_code,
ADD COLUMN billing_phone VARCHAR(20) AFTER billing_country;
