-- =============================================================================
-- CMS Bootstrap SQL  (production-ready, idempotent, MariaDB / MySQL 5.7+)
-- -----------------------------------------------------------------------------
-- Apply this file ONCE on the production database to bring an old server up
-- to speed with the new self-built CMS module.
--
--   mysql -u <db_user> -p <db_name> < docs/sql/cms-bootstrap.sql
--
-- What it does (each step is safe to re-run, nothing is destructive):
--
--   1. Creates the five CMS tables: site_pages, site_media, page_sections,
--      page_section_revisions, page_section_locks (CREATE TABLE IF NOT EXISTS).
--   2. Seeds the four default pages (global / home / contact / courses) and
--      their built-in section keys (INSERT IGNORE).
--   3. Patches the legacy `messages` table for the reply-thread feature
--      (parent_message_id, recipient_name, recipient_email, delivery_status)
--      using INFORMATION_SCHEMA guards so it is safe even if the columns
--      already exist.
--   4. Registers the two CMS migrations in `cake_migrations` so the next run
--      of `bin/cake migrations migrate` will treat them as already applied
--      and not attempt to re-create the tables.
--
-- All foreign keys reference the existing `users(user_id)` table, so it must
-- exist before this script runs. Charset / collation matches the rest of the
-- application (utf8mb4 / utf8mb4_unicode_ci).
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';


-- =============================================================================
-- 1. CMS tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `site_pages` (
    `id`         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_slug`  VARCHAR(80)         NOT NULL,
    `page_title` VARCHAR(255)        NOT NULL,
    `is_active`  TINYINT(1)          NOT NULL DEFAULT 1,
    `sort_order` INT(11)             NOT NULL DEFAULT 0,
    `created_at` DATETIME            NOT NULL,
    `updated_at` DATETIME            NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_site_pages_slug` (`page_slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `site_media` (
    `id`              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `file_name`       VARCHAR(255)        NOT NULL,
    `file_path`       VARCHAR(500)        NOT NULL,
    `file_url`        VARCHAR(500)        NOT NULL,
    `mime_type`       VARCHAR(100)        NOT NULL,
    `file_size`       INT(10) UNSIGNED    NOT NULL,
    `alt_text`        VARCHAR(500)        DEFAULT NULL,
    `uploaded_by_id`  BIGINT(20) UNSIGNED DEFAULT NULL,
    `uploaded_at`     DATETIME            NOT NULL,
    `updated_at`      DATETIME            NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_site_media_uploader` (`uploaded_by_id`),
    CONSTRAINT `fk_site_media_uploader`
        FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `page_sections` (
    `id`             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_id`        BIGINT(20) UNSIGNED NOT NULL,
    `section_key`    VARCHAR(120)        NOT NULL,
    `section_label`  VARCHAR(255)        NOT NULL,
    `section_hint`   TEXT                DEFAULT NULL,
    `content_type`   VARCHAR(20)         NOT NULL,
    `content_value`  LONGTEXT            DEFAULT NULL,
    `media_id`       BIGINT(20) UNSIGNED DEFAULT NULL,
    `is_active`      TINYINT(1)          NOT NULL DEFAULT 1,
    `sort_order`     INT(11)             NOT NULL DEFAULT 0,
    `updated_at`     DATETIME            NOT NULL,
    `updated_by_id`  BIGINT(20) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_page_sections_key` (`page_id`, `section_key`),
    KEY `idx_page_sections_order` (`page_id`, `sort_order`),
    KEY `idx_page_sections_media` (`media_id`),
    KEY `idx_page_sections_updated_by` (`updated_by_id`),
    CONSTRAINT `fk_page_sections_page`
        FOREIGN KEY (`page_id`) REFERENCES `site_pages` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_page_sections_media`
        FOREIGN KEY (`media_id`) REFERENCES `site_media` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_page_sections_updated_by`
        FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `page_section_revisions` (
    `id`                       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_id`               BIGINT(20) UNSIGNED NOT NULL,
    `content_value_snapshot`   LONGTEXT            DEFAULT NULL,
    `media_id_snapshot`        BIGINT(20) UNSIGNED DEFAULT NULL,
    `changed_by_id`            BIGINT(20) UNSIGNED DEFAULT NULL,
    `changed_at`               DATETIME            NOT NULL,
    `change_summary`           VARCHAR(500)        DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_revisions_section_time` (`section_id`, `changed_at`),
    KEY `idx_revisions_changed_by` (`changed_by_id`),
    CONSTRAINT `fk_page_section_revisions_section`
        FOREIGN KEY (`section_id`) REFERENCES `page_sections` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_page_section_revisions_changed_by`
        FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `page_section_locks` (
    `id`            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_id`    BIGINT(20) UNSIGNED NOT NULL,
    `locked_by_id`  BIGINT(20) UNSIGNED NOT NULL,
    `locked_at`     DATETIME            NOT NULL,
    `expires_at`    DATETIME            NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_section_locks_section` (`section_id`),
    KEY `idx_section_locks_expires` (`expires_at`),
    KEY `idx_section_locks_locked_by` (`locked_by_id`),
    CONSTRAINT `fk_page_section_locks_section`
        FOREIGN KEY (`section_id`) REFERENCES `page_sections` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_page_section_locks_locked_by`
        FOREIGN KEY (`locked_by_id`) REFERENCES `users` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


-- =============================================================================
-- 2. Default seed data  (4 pages + 20 sections)
--    Re-runnable: INSERT IGNORE skips rows whose unique key already exists.
-- =============================================================================

INSERT IGNORE INTO `site_pages` (`page_slug`, `page_title`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
    ('global',  'Global / Brand',    1, 0, NOW(), NOW()),
    ('home',    'Home Page',         1, 1, NOW(), NOW()),
    ('contact', 'Contact / Enquiry', 1, 2, NOW(), NOW()),
    ('courses', 'Courses Listing',   1, 3, NOW(), NOW());

-- Resolve the page_id values once and reuse via @global_id, @home_id, etc.
SELECT `id` INTO @global_id  FROM `site_pages` WHERE `page_slug` = 'global'  LIMIT 1;
SELECT `id` INTO @home_id    FROM `site_pages` WHERE `page_slug` = 'home'    LIMIT 1;
SELECT `id` INTO @contact_id FROM `site_pages` WHERE `page_slug` = 'contact' LIMIT 1;
SELECT `id` INTO @courses_id FROM `site_pages` WHERE `page_slug` = 'courses' LIMIT 1;

-- Global / brand strings (used in <title>, brand mark, footer, nav buttons)
INSERT IGNORE INTO `page_sections`
    (`page_id`, `section_key`, `section_label`, `section_hint`, `content_type`, `content_value`, `is_active`, `sort_order`, `updated_at`)
VALUES
    (@global_id, 'branding.site_name',      'Site Name',           'Used in <title> tags and brand mark across the site.',          'text',  'CandleCraft Academy',                                  1, 1, NOW()),
    (@global_id, 'branding.site_subtitle',  'Site Subtitle',       'Tagline shown under the brand name in the public nav.',        'text',  'Pottery & Knitting Tutoring',                          1, 2, NOW()),
    (@global_id, 'branding.copyright_text', 'Footer Copyright',    'Use {year} for the current year (e.g. "© {year} ...").',       'text',  '© {year} CandleCraft Academy. All rights reserved.',   1, 3, NOW()),
    (@global_id, 'branding.logo_image',     'Logo Image',          'Optional. Replaces the text brand mark when set.',             'image', NULL,                                                   1, 4, NOW()),
    (@global_id, 'branding.favicon_image',  'Favicon',             'Optional override for the browser tab icon.',                  'image', NULL,                                                   1, 5, NOW()),
    (@global_id, 'nav.cta_label',           'Nav CTA Label',       'Public navigation enquiry button label.',                      'text',  'Enquire',                                              1, 6, NOW()),
    (@global_id, 'nav.login_label',         'Nav Login Label',     'Public navigation log-in link label.',                         'text',  'Log In',                                               1, 7, NOW());

-- Home page hero section
INSERT IGNORE INTO `page_sections`
    (`page_id`, `section_key`, `section_label`, `section_hint`, `content_type`, `content_value`, `is_active`, `sort_order`, `updated_at`)
VALUES
    (@home_id, 'hero.eyebrow',             'Hero Eyebrow',          'Small overline above the hero title.',                  'text',  'A Sanctuary For The Creative Soul.', 1, 1, NOW()),
    (@home_id, 'hero.title',               'Hero Title',            'Main headline on the home page.',                       'text',  'CandleCraft Academy',                1, 2, NOW()),
    (@home_id, 'hero.background_image',    'Hero Background',       'Full-bleed image behind the hero copy.',                'image', NULL,                                  1, 3, NOW()),
    (@home_id, 'hero.cta_primary_label',   'Hero Primary Button',   'Label for the "Browse Courses" call-to-action.',        'text',  'Browse Courses',                      1, 4, NOW()),
    (@home_id, 'hero.cta_secondary_label', 'Hero Secondary Button', 'Label for the "Enquire Today" call-to-action.',         'text',  'Enquire Today',                       1, 5, NOW());

-- Contact / enquiry page
INSERT IGNORE INTO `page_sections`
    (`page_id`, `section_key`, `section_label`, `section_hint`, `content_type`, `content_value`, `is_active`, `sort_order`, `updated_at`)
VALUES
    (@contact_id, 'intro.title', 'Page Title',      'Heading shown on the enquiry page.', 'text',     'Enquiry Form',                                                                       1, 1, NOW()),
    (@contact_id, 'intro.body',  'Intro Paragraph', 'Short paragraph above the form.',    'textarea', 'Use the enquiry form below and someone from our team will be in touch shortly.', 1, 2, NOW());

-- Courses listing page
INSERT IGNORE INTO `page_sections`
    (`page_id`, `section_key`, `section_label`, `section_hint`, `content_type`, `content_value`, `is_active`, `sort_order`, `updated_at`)
VALUES
    (@courses_id, 'intro.eyebrow',                 'Eyebrow Label',        'Small overline above the page title.',   'text', 'CandleCraft Academy',                                                                                                              1, 1, NOW()),
    (@courses_id, 'intro.title',                   'Page Title',           'Main heading for the courses landing.',  'text', 'Our Courses',                                                                                                                      1, 2, NOW()),
    (@courses_id, 'category.pottery_title',        'Pottery Card Title',   'Heading on the Pottery category card.',  'text', 'Pottery',                                                                                                                          1, 3, NOW()),
    (@courses_id, 'category.pottery_description',  'Pottery Description',  'Short copy on the Pottery card.',        'html', 'Learn pottery through guided, hands-on lessons that build your skills from basic techniques to creating your own finished pieces.', 1, 4, NOW()),
    (@courses_id, 'category.knitting_title',       'Knitting Card Title',  'Heading on the Knitting category card.', 'text', 'Knitting',                                                                                                                         1, 5, NOW()),
    (@courses_id, 'category.knitting_description', 'Knitting Description', 'Short copy on the Knitting card.',       'html', 'Learn knitting step by step with practical lessons that help you master stitches and create your own handmade projects.',           1, 6, NOW());


-- =============================================================================
-- 3. Legacy `messages` schema self-heal
--    Older deployments may be missing the columns / index that the reply
--    threading feature requires. Each ALTER is guarded with INFORMATION_SCHEMA
--    so the script is safe to re-run.
-- =============================================================================

DROP PROCEDURE IF EXISTS `cms_bootstrap_patch_messages`;
DELIMITER $$
CREATE PROCEDURE `cms_bootstrap_patch_messages`()
BEGIN
    -- Bail out early if the legacy `messages` table does not yet exist
    -- (e.g. running this script on a brand-new database). The CMS module
    -- itself does not depend on it.
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
    ) THEN
        SELECT 'messages table not found - skipping legacy schema patch' AS info;
    ELSE

    -- Add parent_message_id ----------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
          AND COLUMN_NAME  = 'parent_message_id'
    ) THEN
        ALTER TABLE `messages`
            ADD COLUMN `parent_message_id` BIGINT(20) UNSIGNED DEFAULT NULL
                AFTER `receiver_user_id`;
    END IF;

    -- Add recipient_name ------------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
          AND COLUMN_NAME  = 'recipient_name'
    ) THEN
        ALTER TABLE `messages`
            ADD COLUMN `recipient_name` VARCHAR(100) DEFAULT NULL
                AFTER `sender_name`;
    END IF;

    -- Add recipient_email -----------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
          AND COLUMN_NAME  = 'recipient_email'
    ) THEN
        ALTER TABLE `messages`
            ADD COLUMN `recipient_email` VARCHAR(255) DEFAULT NULL
                AFTER `recipient_name`;
    END IF;

    -- Add delivery_status -----------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
          AND COLUMN_NAME  = 'delivery_status'
    ) THEN
        ALTER TABLE `messages`
            ADD COLUMN `delivery_status` VARCHAR(20) DEFAULT NULL;
    END IF;

    -- Index on parent_message_id ---------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
          AND INDEX_NAME   = 'idx_messages_parent_message_id'
    ) THEN
        ALTER TABLE `messages`
            ADD INDEX `idx_messages_parent_message_id` (`parent_message_id`);
    END IF;

    -- Index on recipient_email ------------------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'messages'
          AND INDEX_NAME   = 'idx_messages_recipient_email'
    ) THEN
        ALTER TABLE `messages`
            ADD INDEX `idx_messages_recipient_email` (`recipient_email`);
    END IF;

    -- Foreign key on parent_message_id ---------------------------------------
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA   = DATABASE()
          AND TABLE_NAME     = 'messages'
          AND CONSTRAINT_NAME = 'fk_messages_parent_message_id'
    ) THEN
        ALTER TABLE `messages`
            ADD CONSTRAINT `fk_messages_parent_message_id`
                FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`message_id`)
                ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;

    END IF;  -- messages table exists guard
END$$
DELIMITER ;

CALL `cms_bootstrap_patch_messages`();
DROP PROCEDURE `cms_bootstrap_patch_messages`;


-- =============================================================================
-- 4. Register the two CMS migrations as already applied
--    so `bin/cake migrations migrate` on the server will skip them.
--    Safe to re-run thanks to INSERT IGNORE on the unique (version, plugin) key.
-- =============================================================================

INSERT IGNORE INTO `cake_migrations` (`version`, `migration_name`, `plugin`, `start_time`, `end_time`, `breakpoint`) VALUES
    (20260511020000, 'CreateCmsTables',       NULL, NOW(), NOW(), 0),
    (20260511020100, 'SeedDefaultCmsContent', NULL, NOW(), NOW(), 0);


-- =============================================================================
-- Done. Suggested verification:
--
--   SELECT page_slug, page_title FROM site_pages ORDER BY sort_order;
--   SELECT COUNT(*) AS sections FROM page_sections;
--   SHOW COLUMNS FROM messages LIKE 'parent_message_id';
--
-- Expected: 4 pages, 20 sections, parent_message_id column present.
-- =============================================================================
