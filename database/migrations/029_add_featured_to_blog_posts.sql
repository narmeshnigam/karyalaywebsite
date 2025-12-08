-- Migration: Add is_featured to blog_posts table
-- Date: 2025-12-08
-- Description: Adds is_featured field for homepage display

ALTER TABLE blog_posts ADD COLUMN is_featured BOOLEAN NOT NULL DEFAULT FALSE AFTER status;
