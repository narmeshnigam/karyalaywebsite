-- Migration: Add icon_image column to solutions table
-- Date: 2025-12-08
-- Description: Adds icon_image field to store PNG icon for each solution

ALTER TABLE solutions ADD COLUMN icon_image VARCHAR(500) NULL AFTER description;

-- Add comment for documentation
-- icon_image: URL/path to the PNG icon image for the solution (displayed on homepage)
