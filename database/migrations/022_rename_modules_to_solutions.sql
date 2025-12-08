-- Rename modules table to solutions
RENAME TABLE modules TO solutions;

-- Update index name
ALTER TABLE solutions DROP INDEX IF EXISTS idx_modules_status_order;
CREATE INDEX idx_solutions_status_order ON solutions(status, display_order);

-- Rename modules_used column in case_studies to solutions_used
ALTER TABLE case_studies CHANGE COLUMN modules_used solutions_used JSON;

-- Rename related_modules column in features to related_solutions
ALTER TABLE features CHANGE COLUMN related_modules related_solutions JSON;
