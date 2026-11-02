-- =====================================================================
-- Ramom SaaS "Paper Maker" Add-on — Installation Schema
-- Engine: InnoDB | Charset: utf8mb4 (Urdu/Arabic-safe)
-- Assumes existing Ramom core tables: branches(id), class(id),
-- section(id), subject(id) — adjust table names below if your core
-- uses different singular/plural naming (the QR addon example uses
-- `class`, `section`, `student`, singular — verify against your DB).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pm_question_bank` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id`       INT UNSIGNED NOT NULL,
  `class_id`        INT UNSIGNED NOT NULL,
  `section_id`      INT UNSIGNED DEFAULT NULL,
  `subject_id`      INT UNSIGNED NOT NULL,
  `chapter_name`    VARCHAR(191) NOT NULL DEFAULT '',
  `question_type`   ENUM('MCQ','Short','Long','True/False','Fill-in-blanks') NOT NULL DEFAULT 'Short',
  `difficulty`      ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium',
  `question_text`   TEXT NOT NULL,
  `options_json`    TEXT DEFAULT NULL,
  `correct_answer`  VARCHAR(500) DEFAULT NULL,
  `marks`           DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `solution_text`   TEXT DEFAULT NULL,
  `status`          TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`      INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pm_qb_branch` (`branch_id`),
  KEY `idx_pm_qb_class_subject` (`branch_id`,`class_id`,`subject_id`),
  KEY `idx_pm_qb_filter` (`branch_id`,`class_id`,`subject_id`,`question_type`,`difficulty`,`status`),
  KEY `idx_pm_qb_chapter` (`branch_id`,`chapter_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pm_templates` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id`          INT UNSIGNED NOT NULL,
  `template_name`      VARCHAR(150) NOT NULL,
  `header_layout`      ENUM('classic','boxed','minimal','logo-left','logo-center') NOT NULL DEFAULT 'classic',
  `header_config`      LONGTEXT DEFAULT NULL,
  `school_logo_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `instructions`       TEXT DEFAULT NULL,
  `watermark_text`     VARCHAR(100) DEFAULT NULL,
  `is_default`         TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pm_tpl_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pm_generated_papers` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id`        INT UNSIGNED NOT NULL,
  `template_id`      INT UNSIGNED DEFAULT NULL,
  `paper_title`      VARCHAR(191) NOT NULL,
  `class_id`         INT UNSIGNED NOT NULL,
  `section_id`       INT UNSIGNED DEFAULT NULL,
  `subject_id`       INT UNSIGNED NOT NULL,
  `total_marks`      DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `time_allowed`     VARCHAR(50) DEFAULT NULL,
  `questions_json`   LONGTEXT NOT NULL,
  `answer_key_json`  LONGTEXT DEFAULT NULL,
  `generation_mode`  ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  `created_by`       INT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pm_gp_branch` (`branch_id`),
  KEY `idx_pm_gp_class_subject` (`branch_id`,`class_id`,`subject_id`),
  KEY `idx_pm_gp_created_by` (`created_by`),
  CONSTRAINT `fk_pm_gp_template` FOREIGN KEY (`template_id`) REFERENCES `pm_templates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- NOTE: Unlike the QR Attendance addon (which reuses existing core
-- tables and ships no install.sql at all), Paper Maker introduces new
-- tables, so this file must still be imported manually — the QR addon
-- example gives no evidence of an automatic SQL-run step in the Addon
-- Manager. Import via phpMyAdmin/CLI after uploading the zip:
--   mysql -u <user> -p <ramom_database> < sql/install.sql
--
-- FK constraints to `branches`/`class`/`subject` were intentionally
-- left out above since exact core table/column names vary by install —
-- add them back once you've confirmed the real names in your schema.

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
