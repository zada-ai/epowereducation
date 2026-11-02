-- Run this on an existing Paper Maker installation to add a per-question
-- description/context field (e.g. a passage, case study, or extra note
-- shown alongside the question — separate from the question text itself
-- and from the solution/answer explanation).
SET @db := DATABASE();
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'pm_question_bank'
      AND COLUMN_NAME = 'description'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE pm_question_bank ADD COLUMN description TEXT NULL AFTER question_text',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
