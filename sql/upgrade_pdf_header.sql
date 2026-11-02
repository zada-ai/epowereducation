SET @db := DATABASE();
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pm_templates'
      AND COLUMN_NAME = 'header_config'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE pm_templates ADD COLUMN header_config LONGTEXT NULL AFTER header_layout',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
