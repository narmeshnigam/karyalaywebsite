-- Add invoice_id column to orders table
-- Invoice ID format: billingYear-billingMonth-first8charOfOrderId
-- Only set for successful payments

ALTER TABLE orders 
ADD COLUMN invoice_id VARCHAR(20) AFTER billing_phone;

-- Add index for invoice_id lookups
ALTER TABLE orders ADD INDEX idx_invoice_id (invoice_id);
