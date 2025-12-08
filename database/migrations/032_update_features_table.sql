-- Add icon_image field to features table
ALTER TABLE features ADD COLUMN icon_image VARCHAR(500) AFTER description;

-- Update related_modules to related_solutions (already done in migration 022)
-- This is just a note that the column was renamed from related_modules to related_solutions
