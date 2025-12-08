-- Add benefits field to solutions table
ALTER TABLE solutions ADD COLUMN benefits JSON AFTER features;
