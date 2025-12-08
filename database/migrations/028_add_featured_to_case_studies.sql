-- Migration: Add cover_image and is_featured to case_studies table
-- Date: 2025-12-08
-- Description: Adds cover_image and is_featured fields for homepage display

ALTER TABLE case_studies ADD COLUMN cover_image VARCHAR(500) NULL AFTER challenge;
ALTER TABLE case_studies ADD COLUMN is_featured BOOLEAN NOT NULL DEFAULT FALSE AFTER status;
