-- Migration: Add setup_instructions column to ports table
-- This column stores rich text setup instructions for each port

ALTER TABLE ports ADD COLUMN setup_instructions TEXT NULL AFTER notes;

-- Add comment for documentation
-- setup_instructions: Rich text HTML content with setup instructions specific to this port
