-- Add featured_on_homepage column to solutions table
ALTER TABLE solutions 
ADD COLUMN featured_on_homepage BOOLEAN NOT NULL DEFAULT FALSE AFTER status,
ADD INDEX idx_featured_on_homepage (featured_on_homepage);
