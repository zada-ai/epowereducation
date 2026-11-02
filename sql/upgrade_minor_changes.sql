-- Run this on an existing Paper Maker installation.
CREATE TABLE IF NOT EXISTS `pm_chapters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `chapter_name` VARCHAR(191) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pm_chapter` (`branch_id`,`class_id`,`subject_id`,`chapter_name`),
  KEY `idx_pm_chapter_lookup` (`branch_id`,`class_id`,`subject_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pm_templates` ADD COLUMN `header_config` LONGTEXT NULL AFTER `header_layout`;
