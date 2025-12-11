-- Add file_path column to media_assets table for proper path handling
-- This stores the relative path to the file, allowing dynamic URL construction

ALTER TABLE media_assets 
ADD COLUMN file_path VARCHAR(500) NULL AFTER url;

-- Add index for file_path lookups
CREATE INDEX idx_media_assets_file_path ON media_assets(file_path);
