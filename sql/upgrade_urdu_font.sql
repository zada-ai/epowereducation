-- Run this on an existing Paper Maker installation to add Urdu font support.
SET @db := DATABASE();
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pm_templates'
      AND COLUMN_NAME = 'pdf_font'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE pm_templates ADD COLUMN pdf_font VARCHAR(50) NOT NULL DEFAULT ''default'' AFTER header_layout',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
