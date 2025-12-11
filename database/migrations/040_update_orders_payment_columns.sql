-- Update orders table payment columns
-- Rename payment_gateway_id to pg_order_id and add pg_payment_id

-- Rename payment_gateway_id to pg_order_id
ALTER TABLE orders 
CHANGE COLUMN payment_gateway_id pg_order_id VARCHAR(255);

-- Add new column for payment gateway payment ID
ALTER TABLE orders 
ADD COLUMN pg_payment_id VARCHAR(255) AFTER pg_order_id;

-- Add index for pg_payment_id for faster lookups
ALTER TABLE orders 
ADD INDEX idx_pg_payment_id (pg_payment_id);
