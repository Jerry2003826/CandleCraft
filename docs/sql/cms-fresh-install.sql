-- =============================================================================
-- CandleCraft  ·  Fresh-Install SQL Snapshot
-- -----------------------------------------------------------------------------
-- Apply this ONCE to a brand-new (empty) database to bring it to the same
-- state as the latest `main` branch:
--
--     mysql -u <db_user> -p <db_name> < docs/sql/cms-fresh-install.sql
--
-- What it contains:
--   * 30 tables (everything from base schema + all 32 migrations applied,
--     including the 5 CMS tables, payment_refunds, payment_disputes,
--     teacher_blocked_dates, page_section_locks, etc.).
--   * 2 read-only views (vw_booking_details, vw_payment_summary).
--   * `cake_migrations` rows pre-populated for every migration so
--     `bin/cake migrations migrate` runs as a no-op on first deploy.
--   * Demo accounts and seed data:
--       - admin@candlecraft.com   (role: admin,    default password: admin123)
--       - emma.clay@candlecraft.com (role: teacher, default password: alice123 *)
--       - alice.wong@candlecraft.com (role: student, default password: alice123)
--     plus a sample course / class / booking / messages so the admin UI is
--     not empty on first login.
--
--   * (*) NOTE: The Emma + Alice password hashes are dev defaults. On
--     production you MUST log in as admin and reset every demo password
--     immediately, or replace these INSERTs with your own data.
--
-- Idempotent: every CREATE TABLE uses IF NOT EXISTS and every INSERT uses
-- INSERT IGNORE, so re-running this file on a partially-populated database
-- never overwrites existing rows.
--
-- Charset / engine: utf8mb4 / utf8mb4_unicode_ci / InnoDB throughout.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,NO_AUTO_VALUE_ON_ZERO';

/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uk_admins_user_id` (`user_id`),
  CONSTRAINT `fk_admins_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Administrator profile data';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ai_interactions` (
  `interaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `interaction_at` datetime NOT NULL DEFAULT current_timestamp(),
  `input_text` longtext NOT NULL,
  `response_text` longtext NOT NULL,
  `interaction_type` enum('chatbot','faq','recommendation','support','other') NOT NULL DEFAULT 'chatbot',
  PRIMARY KEY (`interaction_id`),
  KEY `idx_ai_interactions_user_id` (`user_id`),
  KEY `idx_ai_interactions_interaction_at` (`interaction_at`),
  KEY `idx_ai_interactions_interaction_type` (`interaction_type`),
  CONSTRAINT `fk_ai_interactions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI interaction logs';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_interactions` WRITE;
/*!40000 ALTER TABLE `ai_interactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_interactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `attendance_records` (
  `attendance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `marked_by_teacher_id` bigint(20) unsigned DEFAULT NULL,
  `attendance_date` datetime NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `attendance_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `uk_attendance_records_booking_id` (`booking_id`),
  KEY `fk_attendance_records_marked_by_teacher_id` (`marked_by_teacher_id`),
  KEY `idx_attendance_records_status` (`attendance_status`),
  KEY `idx_attendance_records_date` (`attendance_date`),
  CONSTRAINT `fk_attendance_records_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_records_marked_by_teacher_id` FOREIGN KEY (`marked_by_teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Attendance entries for booked classes';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `booking_date` datetime NOT NULL DEFAULT current_timestamp(),
  `booking_status` enum('pending','confirmed','cancelled','completed','waitlisted') NOT NULL DEFAULT 'pending',
  `price_at_booking` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `uk_bookings_class_student` (`class_id`,`student_id`),
  KEY `fk_bookings_parent_student` (`parent_id`,`student_id`),
  KEY `idx_bookings_parent_id` (`parent_id`),
  KEY `idx_bookings_student_id` (`student_id`),
  KEY `idx_bookings_booking_status` (`booking_status`),
  KEY `idx_bookings_booking_date` (`booking_date`),
  KEY `idx_bookings_reminder_sent_at` (`reminder_sent_at`),
  CONSTRAINT `fk_bookings_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_parent_student` FOREIGN KEY (`parent_id`, `student_id`) REFERENCES `parent_students` (`parent_id`, `student_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_bookings_price_non_negative` CHECK (`price_at_booking` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Class reservations made in the portal; parent linkage is optional legacy data';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT IGNORE INTO `bookings` (`booking_id`, `class_id`, `parent_id`, `student_id`, `booking_date`, `booking_status`, `price_at_booking`, `notes`, `reminder_sent_at`, `created_at`, `updated_at`) VALUES (1,1,NULL,1,'2026-05-11 07:34:41','confirmed',89.00,'Test booking for reminder pipeline','2026-05-11 07:36:10','2026-05-11 07:34:41','2026-05-10 21:36:10');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `cake_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `plugin` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `version_plugin_unique` (`version`,`plugin`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cake_migrations` WRITE;
/*!40000 ALTER TABLE `cake_migrations` DISABLE KEYS */;
INSERT IGNORE INTO `cake_migrations` (`id`, `version`, `migration_name`, `plugin`, `start_time`, `end_time`, `breakpoint`) VALUES (1,20260305085600,'CreateCategories',NULL,'2026-03-28 05:33:28','2026-03-28 05:33:28',0),
(2,20260324021500,'AddMessageContactFields',NULL,'2026-03-28 05:33:28','2026-03-28 05:33:28',0),
(3,20260328080001,'CreatePayments',NULL,'2026-03-28 05:35:10','2026-03-28 05:35:10',0),
(4,20260328080002,'CreateLearningResources',NULL,'2026-03-28 05:35:32','2026-03-28 05:35:32',0),
(5,20260328080003,'CreateNotifications',NULL,'2026-03-28 05:41:15','2026-03-28 05:41:15',0),
(6,20260328080004,'CreateTeacherAvailabilities',NULL,'2026-03-28 05:43:39','2026-03-28 05:43:39',0),
(7,20260413000001,'AddAgeVerifiedToUsers',NULL,'2026-04-13 09:40:52','2026-04-13 09:40:52',0),
(8,20260415070000,'AddDeclaredAgeToStudentsAndRelaxBookingParent',NULL,'2026-04-15 04:40:50','2026-04-15 04:40:51',0),
(9,20260415183000,'AlignWorkflowMessagingPaymentsAndReminders',NULL,'2026-04-15 08:13:14','2026-04-15 08:13:15',0),
(10,20260419090000,'AlignPaymentsForCheckoutStateMachine',NULL,'2026-04-20 09:49:15','2026-04-20 09:49:15',0),
(11,20260420010000,'CreatePaymentWebhookIncidents',NULL,'2026-04-20 09:49:15','2026-04-20 09:49:15',0),
(12,20260420130000,'AddEventIdToPaymentWebhookIncidents',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(13,20260420170000,'CreateStripeWebhookEvents',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(14,20260420190000,'HardenStripeWebhookEventsLedger',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(15,20260420193000,'RepairStripeWebhookEventLedgerBusinessKeys',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(16,20260420203000,'AddSuspiciousAuditToStripeWebhookEvents',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(17,20260420210000,'AddStripeWebhookSuspiciousAuditIndexes',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(18,20260420213000,'AddReplayAndSuppressedAuditToStripeWebhookEvents',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(19,20260421000000,'AlignLearningResourcesSchema',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(20,20260421010000,'AllowNullPaymentDateForStateMachine',NULL,'2026-04-20 09:49:27','2026-04-20 09:49:27',0),
(21,20260421020000,'RepairPaymentWebhookIncidentAndStripeSchemas',NULL,'2026-04-20 09:56:16','2026-04-20 09:56:16',0),
(22,20260422030000,'ExpandUserRoleForCurrentPortals',NULL,'2026-04-26 20:06:36','2026-04-26 20:06:37',0),
(23,20260422150000,'ClearMinorAdultVerifications',NULL,'2026-04-26 20:06:37','2026-04-26 20:06:37',0),
(24,20260423090000,'AddStripeProductionFieldsToPayments',NULL,'2026-04-26 20:06:37','2026-04-26 20:06:37',0),
(25,20260423091000,'CreatePaymentRefunds',NULL,'2026-04-26 20:06:37','2026-04-26 20:06:37',0),
(26,20260423092000,'CreatePaymentDisputes',NULL,'2026-04-26 20:06:37','2026-04-26 20:06:37',0),
(27,20260423093000,'AllowDisputedPaymentStatus',NULL,'2026-04-26 20:06:37','2026-04-26 20:06:37',0),
(28,20260426173000,'AddPasswordResetFieldsToUsers',NULL,'2026-04-26 20:06:37','2026-04-26 20:06:37',0),
(29,20260506120000,'CreateTeacherBlockedDates',NULL,'2026-05-11 07:30:57','2026-05-11 07:30:57',0),
(30,20260511020000,'CreateCmsTables',NULL,'2026-05-11 08:49:45','2026-05-11 08:49:46',0),
(31,20260511020100,'SeedDefaultCmsContent',NULL,'2026-05-11 08:50:29','2026-05-11 08:50:29',0);
/*!40000 ALTER TABLE `cake_migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `cake_seeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin` varchar(100) DEFAULT NULL,
  `seed_name` varchar(100) NOT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cake_seeds` WRITE;
/*!40000 ALTER TABLE `cake_seeds` DISABLE KEYS */;
/*!40000 ALTER TABLE `cake_seeds` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `classes` (
  `class_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_code` varchar(30) NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `location` varchar(150) NOT NULL,
  `capacity` int(10) unsigned NOT NULL DEFAULT 20,
  `class_status` enum('scheduled','ongoing','completed','cancelled','full') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `uk_classes_class_code` (`class_code`),
  KEY `idx_classes_course_id` (`course_id`),
  KEY `idx_classes_teacher_id` (`teacher_id`),
  KEY `idx_classes_start_datetime` (`start_datetime`),
  KEY `idx_classes_status` (`class_status`),
  CONSTRAINT `fk_classes_course_id` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_classes_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_classes_capacity_positive` CHECK (`capacity` > 0),
  CONSTRAINT `chk_classes_time_order` CHECK (`end_datetime` > `start_datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Scheduled class sessions';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT IGNORE INTO `classes` (`class_id`, `class_code`, `course_id`, `teacher_id`, `start_datetime`, `end_datetime`, `location`, `capacity`, `class_status`, `notes`, `created_at`, `updated_at`) VALUES (1,'POT-T24H',1,1,'2026-05-12 07:34:41','2026-05-12 09:34:41','Studio A',12,'scheduled',NULL,'2026-05-11 07:34:41','2026-05-11 07:34:41');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_name` varchar(100) NOT NULL,
  `course_type` varchar(50) NOT NULL,
  `course_level` enum('beginner','intermediate','advanced','all_levels') NOT NULL DEFAULT 'beginner',
  `course_price` decimal(10,2) NOT NULL,
  `course_description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `uk_courses_name_level` (`course_name`,`course_level`),
  KEY `idx_courses_type` (`course_type`),
  KEY `idx_courses_level` (`course_level`),
  KEY `idx_courses_active` (`is_active`),
  CONSTRAINT `chk_courses_price_non_negative` CHECK (`course_price` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Course master data';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT IGNORE INTO `courses` (`course_id`, `course_name`, `course_type`, `course_level`, `course_price`, `course_description`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Pottery Wheel Basics','pottery','beginner',89.00,'Intro to wheel throwing',1,'2026-05-11 07:34:41','2026-05-11 07:34:41');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `learning_resources` (
  `resource_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL,
  `uploaded_by_teacher_id` bigint(20) unsigned DEFAULT NULL,
  `resource_name` varchar(150) NOT NULL,
  `resource_type` enum('pdf','video','image','document','link','other') NOT NULL DEFAULT 'other',
  `resource_url` varchar(500) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `resource_description` text DEFAULT NULL,
  `resource_status` enum('active','archived') NOT NULL DEFAULT 'active',
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`resource_id`),
  KEY `fk_learning_resources_uploaded_by_teacher_id` (`uploaded_by_teacher_id`),
  KEY `idx_learning_resources_class_id` (`class_id`),
  KEY `idx_learning_resources_resource_status` (`resource_status`),
  KEY `idx_learning_resources_resource_type` (`resource_type`),
  CONSTRAINT `fk_learning_resources_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_learning_resources_uploaded_by_teacher_id` FOREIGN KEY (`uploaded_by_teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_learning_resources_location` CHECK (`resource_url` is not null or `file_path` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Learning materials assigned to classes';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `learning_resources` WRITE;
/*!40000 ALTER TABLE `learning_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `learning_resources` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_user_id` bigint(20) unsigned DEFAULT NULL,
  `receiver_user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_message_id` bigint(20) unsigned DEFAULT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `sender_email` varchar(255) DEFAULT NULL,
  `sender_phone` varchar(30) DEFAULT NULL,
  `source_page` varchar(255) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message_text` text NOT NULL,
  `message_type` enum('internal','contact_form') NOT NULL DEFAULT 'internal',
  `message_status` enum('unread','read','replied','archived') NOT NULL DEFAULT 'unread',
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_sender_user_id` (`sender_user_id`),
  KEY `idx_messages_receiver_user_id` (`receiver_user_id`),
  KEY `idx_messages_message_status` (`message_status`),
  KEY `idx_messages_sent_at` (`sent_at`),
  KEY `idx_messages_message_type` (`message_type`),
  KEY `idx_messages_source_page` (`source_page`),
  KEY `idx_messages_parent_message_id` (`parent_message_id`),
  CONSTRAINT `fk_messages_receiver_user_id` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_sender_user_id` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_messages_contact_form_sender_email` CHECK (`message_type` <> 'contact_form' or `sender_email` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Internal messages and contact form submissions';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT IGNORE INTO `messages` (`message_id`, `sender_user_id`, `receiver_user_id`, `parent_message_id`, `sender_name`, `recipient_name`, `recipient_email`, `sender_email`, `sender_phone`, `source_page`, `subject`, `message_text`, `message_type`, `message_status`, `sent_at`, `updated_at`, `delivery_status`) VALUES (2,NULL,2,NULL,'demo dad',NULL,NULL,'demo@example.com','0411111111','courses-1','Book a Class - Pottery','demo enquiry text','contact_form','replied','2026-05-11 19:22:15','2026-05-11 19:22:15',NULL);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT IGNORE INTO `notifications` (`id`, `user_id`, `title`, `message`, `notification_type`, `is_read`, `created`) VALUES (1,3,'Booking Confirmed','Your booking for \"Introduction to Pottery\" on Wed 22 Apr 2026, 10:00am has been confirmed.','booking_confirmation',1,'2026-03-28 07:23:10'),
(2,3,'Payment Received','Payment of $150.00 for \"Introduction to Pottery\" has been received.','payment_receipt',1,'2026-03-28 07:23:34'),
(3,7,'Booking Confirmed','Your booking for \"Introduction to Pottery\" on Fri 17 Apr 2026, 10:00am has been confirmed.','booking_confirmation',0,'2026-04-15 06:50:23'),
(4,7,'Payment Received','Payment of $150.00 for \"Introduction to Pottery\" has been received.','payment_receipt',0,'2026-04-15 06:50:28'),
(5,4,'Class Reminder','Reminder: Pottery Wheel Basics starts at Tue 12 May 2026, 7:34am.','class_reminder',0,'2026-05-11 07:36:10');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `page_section_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) unsigned NOT NULL,
  `locked_by_id` bigint(20) unsigned NOT NULL,
  `locked_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section_locks_section` (`section_id`),
  KEY `idx_section_locks_expires` (`expires_at`),
  KEY `locked_by_id` (`locked_by_id`),
  CONSTRAINT `1` FOREIGN KEY (`section_id`) REFERENCES `page_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`locked_by_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_section_locks` WRITE;
/*!40000 ALTER TABLE `page_section_locks` DISABLE KEYS */;
INSERT IGNORE INTO `page_section_locks` (`id`, `section_id`, `locked_by_id`, `locked_at`, `expires_at`) VALUES (10,4,2,'2026-05-11 09:30:48','2026-05-11 09:35:48'),
(11,1,2,'2026-05-11 09:31:24','2026-05-11 09:36:24'),
(12,3,2,'2026-05-11 18:57:18','2026-05-11 19:02:18');
/*!40000 ALTER TABLE `page_section_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `page_section_revisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) unsigned NOT NULL,
  `content_value_snapshot` text DEFAULT NULL,
  `media_id_snapshot` bigint(20) unsigned DEFAULT NULL,
  `changed_by_id` bigint(20) unsigned DEFAULT NULL,
  `changed_at` datetime NOT NULL,
  `change_summary` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_revisions_section_time` (`section_id`,`changed_at`),
  KEY `changed_by_id` (`changed_by_id`),
  CONSTRAINT `1` FOREIGN KEY (`section_id`) REFERENCES `page_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_section_revisions` WRITE;
/*!40000 ALTER TABLE `page_section_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_section_revisions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `page_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `section_key` varchar(120) NOT NULL,
  `section_label` varchar(255) NOT NULL,
  `section_hint` text DEFAULT NULL,
  `content_type` varchar(20) NOT NULL,
  `content_value` text DEFAULT NULL,
  `media_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL,
  `updated_by_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_sections_key` (`page_id`,`section_key`),
  KEY `idx_page_sections_order` (`page_id`,`sort_order`),
  KEY `idx_page_sections_media` (`media_id`),
  KEY `updated_by_id` (`updated_by_id`),
  CONSTRAINT `1` FOREIGN KEY (`page_id`) REFERENCES `site_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`media_id`) REFERENCES `site_media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `3` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_sections` WRITE;
/*!40000 ALTER TABLE `page_sections` DISABLE KEYS */;
INSERT IGNORE INTO `page_sections` (`id`, `page_id`, `section_key`, `section_label`, `section_hint`, `content_type`, `content_value`, `media_id`, `is_active`, `sort_order`, `updated_at`, `updated_by_id`) VALUES (1,1,'branding.site_name','Site Name','Used in <title> tags and brand mark across the site.','text','CandleCraft Academy',NULL,1,1,'2026-05-11 09:21:18',2),
(2,1,'branding.site_subtitle','Site Subtitle','Tagline shown under the brand name in the public nav.','text','Pottery & Knitting Tutoring',NULL,1,2,'2026-05-11 08:50:29',NULL),
(3,1,'branding.copyright_text','Footer Copyright','Use {year} for the current year (e.g. \"© {year} ...\").','text','© {year} CandleCraft Academy. All rights reserved.',NULL,1,3,'2026-05-11 08:50:29',NULL),
(4,1,'branding.logo_image','Logo Image','Optional. Replaces the text brand mark when set.','image',NULL,NULL,1,4,'2026-05-11 08:50:29',NULL),
(5,1,'branding.favicon_image','Favicon','Optional override for the browser tab icon.','image',NULL,NULL,1,5,'2026-05-11 08:50:29',NULL),
(6,1,'nav.cta_label','Nav CTA Label','Public navigation enquiry button label.','text','Enquire',NULL,1,6,'2026-05-11 08:50:29',NULL),
(7,1,'nav.login_label','Nav Login Label','Public navigation log-in link label.','text','Log In',NULL,1,7,'2026-05-11 08:50:29',NULL),
(8,2,'hero.eyebrow','Hero Eyebrow','Small overline above the hero title.','text','A Sanctuary For The Creative Soul.',NULL,1,1,'2026-05-11 08:50:29',NULL),
(9,2,'hero.title','Hero Title','Main headline on the home page.','text','CandleCraft Academy',NULL,1,2,'2026-05-11 08:50:29',NULL),
(10,2,'hero.background_image','Hero Background','Full-bleed image behind the hero copy.','image',NULL,NULL,1,3,'2026-05-11 08:50:29',NULL),
(11,2,'hero.cta_primary_label','Hero Primary Button','Label for the \"Browse Courses\" call-to-action.','text','Browse Courses',NULL,1,4,'2026-05-11 08:50:29',NULL),
(12,2,'hero.cta_secondary_label','Hero Secondary Button','Label for the \"Enquire Today\" call-to-action.','text','Enquire Today',NULL,1,5,'2026-05-11 08:50:29',NULL),
(13,3,'intro.title','Page Title','Heading shown on the enquiry page.','text','Enquiry Form',NULL,1,1,'2026-05-11 08:50:29',NULL),
(14,3,'intro.body','Intro Paragraph','Short paragraph above the form.','textarea','Use the enquiry form below and someone from our team will be in touch shortly.',NULL,1,2,'2026-05-11 08:50:29',NULL),
(15,4,'intro.eyebrow','Eyebrow Label','Small overline above the page title.','text','CandleCraft Academy',NULL,1,1,'2026-05-11 08:50:29',NULL),
(16,4,'intro.title','Page Title','Main heading for the courses landing.','text','Our Courses',NULL,1,2,'2026-05-11 08:50:29',NULL),
(17,4,'category.pottery_title','Pottery Card Title','Heading on the Pottery category card.','text','Pottery',NULL,1,3,'2026-05-11 08:50:29',NULL),
(18,4,'category.pottery_description','Pottery Description','Short copy on the Pottery card.','html','Learn pottery through guided, hands-on lessons that build your skills from basic techniques to creating your own finished pieces.',NULL,1,4,'2026-05-11 08:50:29',NULL),
(19,4,'category.knitting_title','Knitting Card Title','Heading on the Knitting category card.','text','Knitting',NULL,1,5,'2026-05-11 08:50:29',NULL),
(20,4,'category.knitting_description','Knitting Description','Short copy on the Knitting card.','html','Learn knitting step by step with practical lessons that help you master stitches and create your own handmade projects.',NULL,1,6,'2026-05-11 08:50:29',NULL);
/*!40000 ALTER TABLE `page_sections` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `parent_students` (
  `parent_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `relationship_to_student` varchar(50) NOT NULL,
  `is_primary_guardian` tinyint(1) NOT NULL DEFAULT 0,
  `can_pick_up` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`parent_id`,`student_id`),
  KEY `idx_parent_students_student_id` (`student_id`),
  KEY `idx_parent_students_relationship` (`relationship_to_student`),
  CONSTRAINT `fk_parent_students_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_parent_students_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Associates parents with one or more students';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `parent_students` WRITE;
/*!40000 ALTER TABLE `parent_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_students` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `parents` (
  `parent_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_name` varchar(100) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`parent_id`),
  UNIQUE KEY `uk_parents_user_id` (`user_id`),
  KEY `idx_parents_phone_number` (`phone_number`),
  CONSTRAINT `fk_parents_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parent profiles';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `parents` WRITE;
/*!40000 ALTER TABLE `parents` DISABLE KEYS */;
/*!40000 ALTER TABLE `parents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payment_disputes` (
  `dispute_record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `stripe_dispute_id` varchar(100) NOT NULL,
  `stripe_charge_id` varchar(100) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'AUD',
  `reason` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `evidence_due_by` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `raw_payload` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`dispute_record_id`),
  UNIQUE KEY `uk_payment_disputes_stripe_dispute_id` (`stripe_dispute_id`),
  KEY `idx_payment_disputes_payment_id` (`payment_id`),
  KEY `idx_payment_disputes_status_due` (`status`,`evidence_due_by`),
  CONSTRAINT `1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_disputes` WRITE;
/*!40000 ALTER TABLE `payment_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_disputes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payment_profiles` (
  `payment_profile_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `billing_name` varchar(150) NOT NULL,
  `billing_email` varchar(255) NOT NULL,
  `billing_phone` varchar(30) DEFAULT NULL,
  `billing_address_line1` varchar(255) DEFAULT NULL,
  `billing_address_line2` varchar(255) DEFAULT NULL,
  `billing_city` varchar(120) DEFAULT NULL,
  `billing_state` varchar(120) DEFAULT NULL,
  `billing_postcode` varchar(20) DEFAULT NULL,
  `billing_country` varchar(120) DEFAULT NULL,
  `preferred_payment_method` varchar(30) NOT NULL DEFAULT 'card',
  `profile_status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_profile_id`),
  KEY `idx_payment_profiles_user_id` (`user_id`),
  KEY `idx_payment_profiles_user_default` (`user_id`,`is_default`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_profiles` WRITE;
/*!40000 ALTER TABLE `payment_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payment_refunds` (
  `refund_record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `stripe_refund_id` varchar(100) DEFAULT NULL,
  `stripe_charge_id` varchar(100) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'AUD',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `reason` varchar(100) DEFAULT NULL,
  `initiated_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `failure_message` text DEFAULT NULL,
  `raw_payload` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`refund_record_id`),
  UNIQUE KEY `uk_payment_refunds_stripe_refund_id` (`stripe_refund_id`),
  KEY `idx_payment_refunds_payment_id` (`payment_id`),
  KEY `idx_payment_refunds_status` (`status`),
  CONSTRAINT `1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_refunds` WRITE;
/*!40000 ALTER TABLE `payment_refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_refunds` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payment_webhook_incidents` (
  `incident_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `session_id` varchar(120) NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `reason_code` varchar(100) NOT NULL,
  `severity` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `context_json` text DEFAULT NULL,
  `payload_hash` varchar(64) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `event_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`incident_id`),
  UNIQUE KEY `uk_payment_webhook_incidents_event_reason_status` (`event_id`,`reason_code`,`status`),
  KEY `idx_payment_webhook_incidents_status` (`status`),
  KEY `idx_payment_webhook_incidents_event_type` (`event_type`),
  KEY `idx_payment_webhook_incidents_session_id` (`session_id`),
  KEY `idx_payment_webhook_incidents_reason_code` (`reason_code`),
  KEY `idx_payment_webhook_incidents_created_at` (`created_at`),
  KEY `payment_id` (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `resolved_by_admin_id` (`resolved_by_admin_id`),
  KEY `idx_payment_webhook_incidents_event_id` (`event_id`),
  CONSTRAINT `1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `3` FOREIGN KEY (`resolved_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payment_webhook_incidents` WRITE;
/*!40000 ALTER TABLE `payment_webhook_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_webhook_incidents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'AUD',
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','card','bank_transfer','online','other') NOT NULL,
  `payment_status` enum('pending','paid','failed','expired','voided','refund_required','refunded','partially_refunded','disputed') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `stripe_session_id` varchar(100) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(100) DEFAULT NULL,
  `stripe_charge_id` varchar(100) DEFAULT NULL,
  `stripe_customer_id` varchar(100) DEFAULT NULL,
  `stripe_invoice_id` varchar(100) DEFAULT NULL,
  `stripe_invoice_pdf_url` varchar(500) DEFAULT NULL,
  `stripe_receipt_url` varchar(500) DEFAULT NULL,
  `stripe_payment_method_type` varchar(50) DEFAULT NULL,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uk_payments_transaction_reference` (`transaction_reference`),
  KEY `idx_payments_booking_id` (`booking_id`),
  KEY `idx_payments_payment_status` (`payment_status`),
  KEY `idx_payments_payment_date` (`payment_date`),
  KEY `idx_payments_payment_method` (`payment_method`),
  KEY `idx_payments_stripe_session_id` (`stripe_session_id`),
  KEY `idx_payments_stripe_payment_intent_id` (`stripe_payment_intent_id`),
  KEY `idx_payments_stripe_charge_id` (`stripe_charge_id`),
  KEY `idx_payments_payment_status_updated` (`payment_status`,`updated_at`),
  CONSTRAINT `fk_payments_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_payments_amount_non_negative` CHECK (`amount` >= 0),
  CONSTRAINT `chk_payments_refunded_amount_range` CHECK (`refunded_amount` >= 0 and `refunded_amount` <= `amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment transactions for bookings. Zero-amount bookings are confirmed locally and do not require Stripe checkout.';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `site_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `alt_text` varchar(500) DEFAULT NULL,
  `uploaded_by_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_media_uploader` (`uploaded_by_id`),
  CONSTRAINT `1` FOREIGN KEY (`uploaded_by_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `site_media` WRITE;
/*!40000 ALTER TABLE `site_media` DISABLE KEYS */;
INSERT IGNORE INTO `site_media` (`id`, `file_name`, `file_path`, `file_url`, `mime_type`, `file_size`, `alt_text`, `uploaded_by_id`, `uploaded_at`, `updated_at`) VALUES (1,'test_pixel.png','uploads/site/2026/05/ff9e886bbb8dd2a0c26a0a81fd8ecefbe505a0c4.png','/uploads/site/2026/05/ff9e886bbb8dd2a0c26a0a81fd8ecefbe505a0c4.png','image/png',68,'Test pixel',2,'2026-05-11 09:17:42','2026-05-11 09:17:42');
/*!40000 ALTER TABLE `site_media` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `site_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(80) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_pages_slug` (`page_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `site_pages` WRITE;
/*!40000 ALTER TABLE `site_pages` DISABLE KEYS */;
INSERT IGNORE INTO `site_pages` (`id`, `page_slug`, `page_title`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1,'global','Global / Brand',1,0,'2026-05-11 08:50:29','2026-05-11 08:50:29'),
(2,'home','Home Page',1,1,'2026-05-11 08:50:29','2026-05-11 08:50:29'),
(3,'contact','Contact / Enquiry',1,2,'2026-05-11 08:50:29','2026-05-11 08:50:29'),
(4,'courses','Courses Listing',1,3,'2026-05-11 08:50:29','2026-05-11 08:50:29');
/*!40000 ALTER TABLE `site_pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `stripe_webhook_events` (
  `webhook_event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` varchar(100) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `business_event_key` varchar(255) DEFAULT NULL,
  `payload_hash` varchar(64) NOT NULL,
  `processing_status` varchar(20) NOT NULL DEFAULT 'processing',
  `suspicious_state` varchar(20) NOT NULL DEFAULT 'clean',
  `suspicious_reason_code` varchar(100) DEFAULT NULL,
  `suspicious_seen_at` datetime DEFAULT NULL,
  `suspicious_business_event_key` varchar(255) DEFAULT NULL,
  `suspicious_payload_hash` varchar(64) DEFAULT NULL,
  `suspicious_target_status` varchar(20) DEFAULT NULL,
  `suspicious_count` int(11) NOT NULL DEFAULT 0,
  `replay_count` int(11) NOT NULL DEFAULT 0,
  `last_replay_event_id` varchar(100) DEFAULT NULL,
  `last_replay_payload_hash` varchar(64) DEFAULT NULL,
  `last_replay_seen_at` datetime DEFAULT NULL,
  `last_suppressed_status_update` varchar(20) DEFAULT NULL,
  `last_suppressed_status_event_id` varchar(100) DEFAULT NULL,
  `last_suppressed_status_payload_hash` varchar(64) DEFAULT NULL,
  `last_suppressed_status_seen_at` datetime DEFAULT NULL,
  `first_seen_at` datetime NOT NULL,
  `processing_started_at` datetime NOT NULL,
  `last_seen_at` datetime NOT NULL,
  PRIMARY KEY (`webhook_event_id`),
  UNIQUE KEY `uk_stripe_webhook_events_event_id` (`event_id`),
  UNIQUE KEY `uk_stripe_webhook_events_business_event_key` (`business_event_key`),
  KEY `idx_stripe_webhook_events_processing_status` (`processing_status`),
  KEY `idx_stripe_webhook_events_suspicious_audit` (`suspicious_state`,`suspicious_seen_at`),
  KEY `idx_stripe_webhook_events_suspicious_reason` (`suspicious_reason_code`,`suspicious_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `stripe_webhook_events` WRITE;
/*!40000 ALTER TABLE `stripe_webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `stripe_webhook_events` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `declared_age` tinyint(3) unsigned DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `student_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `medical_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `uk_students_user_id` (`user_id`),
  KEY `idx_students_name` (`student_name`),
  KEY `idx_students_declared_age` (`declared_age`),
  KEY `idx_students_status` (`student_status`),
  CONSTRAINT `fk_students_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Student records; login is optional';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT IGNORE INTO `students` (`student_id`, `user_id`, `student_name`, `declared_age`, `date_of_birth`, `student_status`, `medical_notes`, `created_at`, `updated_at`) VALUES (1,4,'Alice Wong',22,NULL,'active',NULL,'2026-05-11 07:34:41','2026-05-11 07:34:41');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `teacher_availabilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL COMMENT '1=Monday, 7=Sunday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_teacher_availabilities_teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `teacher_availabilities` WRITE;
/*!40000 ALTER TABLE `teacher_availabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_availabilities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `teacher_blocked_dates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `blocked_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_teacher_blocked_dates_teacher_id` (`teacher_id`),
  CONSTRAINT `1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `teacher_blocked_dates` WRITE;
/*!40000 ALTER TABLE `teacher_blocked_dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_blocked_dates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `teachers` (
  `teacher_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `teacher_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `hire_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`teacher_id`),
  UNIQUE KEY `uk_teachers_user_id` (`user_id`),
  KEY `idx_teachers_name` (`teacher_name`),
  KEY `idx_teachers_status` (`teacher_status`),
  CONSTRAINT `fk_teachers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Teacher profiles';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT IGNORE INTO `teachers` (`teacher_id`, `user_id`, `teacher_name`, `phone_number`, `specialization`, `teacher_status`, `hire_date`, `created_at`, `updated_at`) VALUES (1,3,'Emma Clay',NULL,'Pottery','active',NULL,'2026-05-11 07:34:41','2026-05-11 07:34:41');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_token` varchar(128) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `user_role` varchar(20) NOT NULL,
  `account_status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `age_verified_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `self_declared_adult` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`user_role`),
  KEY `idx_users_account_status` (`account_status`),
  KEY `idx_users_reset_token` (`reset_token`),
  CONSTRAINT `chk_users_username_length` CHECK (char_length(`username`) >= 3)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Central login table for all system accounts';
/*!40101 SET character_set_client = @saved_cs_client */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT IGNORE INTO `users` (`user_id`, `username`, `email`, `password_hash`, `reset_token`, `reset_token_expires`, `user_role`, `account_status`, `age_verified_by_admin`, `self_declared_adult`, `last_login_at`, `created_at`, `updated_at`) VALUES (2,'admin','admin@candlecraft.com','$2y$12$9XVktR4OhmYARbWwY6f45uJ2rCwVLyK2ApgDK7VzNSZ3kLwpJzAhS',NULL,NULL,'admin','active',1,1,NULL,'2026-05-11 07:20:22','2026-05-11 09:07:40'),
(3,'emma.clay','emma.clay@candlecraft.com','$2y$12$S9cv4FaqtdH3RvE8fZsJHupLCOIJFmqaW312n2LGHqIyc7mP1Bici',NULL,NULL,'teacher','active',1,1,NULL,'2026-05-11 07:20:22','2026-05-11 07:20:22'),
(4,'alice.wong','alice.wong@candlecraft.com','$2y$12$uGQTCpvsEqjgDqsnvNwL0.fogTKKgRnp2C6MmFYIvNJV.jos03LGK',NULL,NULL,'student','active',1,1,NULL,'2026-05-11 07:20:22','2026-05-11 20:37:48');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE OR REPLACE VIEW `vw_booking_details` AS SELECT
 1 AS `booking_id`,
  1 AS `booking_date`,
  1 AS `booking_status`,
  1 AS `price_at_booking`,
  1 AS `student_id`,
  1 AS `student_name`,
  1 AS `parent_id`,
  1 AS `parent_name`,
  1 AS `class_id`,
  1 AS `class_code`,
  1 AS `start_datetime`,
  1 AS `end_datetime`,
  1 AS `location`,
  1 AS `class_status`,
  1 AS `course_id`,
  1 AS `course_name`,
  1 AS `course_level`,
  1 AS `teacher_id`,
  1 AS `teacher_name` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE OR REPLACE VIEW `vw_payment_summary` AS SELECT
 1 AS `booking_id`,
  1 AS `student_name`,
  1 AS `parent_name`,
  1 AS `class_code`,
  1 AS `course_name`,
  1 AS `price_at_booking`,
  1 AS `total_recorded_payments`,
  1 AS `total_refunded` */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `vw_booking_details`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE OR REPLACE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `vw_booking_details` AS select `b`.`booking_id` AS `booking_id`,`b`.`booking_date` AS `booking_date`,`b`.`booking_status` AS `booking_status`,`b`.`price_at_booking` AS `price_at_booking`,`s`.`student_id` AS `student_id`,`s`.`student_name` AS `student_name`,`p`.`parent_id` AS `parent_id`,`p`.`parent_name` AS `parent_name`,`c`.`class_id` AS `class_id`,`c`.`class_code` AS `class_code`,`c`.`start_datetime` AS `start_datetime`,`c`.`end_datetime` AS `end_datetime`,`c`.`location` AS `location`,`c`.`class_status` AS `class_status`,`co`.`course_id` AS `course_id`,`co`.`course_name` AS `course_name`,`co`.`course_level` AS `course_level`,`t`.`teacher_id` AS `teacher_id`,`t`.`teacher_name` AS `teacher_name` from (((((`bookings` `b` join `students` `s` on(`s`.`student_id` = `b`.`student_id`)) left join `parents` `p` on(`p`.`parent_id` = `b`.`parent_id`)) join `classes` `c` on(`c`.`class_id` = `b`.`class_id`)) join `courses` `co` on(`co`.`course_id` = `c`.`course_id`)) join `teachers` `t` on(`t`.`teacher_id` = `c`.`teacher_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_payment_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE OR REPLACE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `vw_payment_summary` AS select `b`.`booking_id` AS `booking_id`,`s`.`student_name` AS `student_name`,`p`.`parent_name` AS `parent_name`,`c`.`class_code` AS `class_code`,`co`.`course_name` AS `course_name`,`b`.`price_at_booking` AS `price_at_booking`,coalesce(sum(case when `py`.`payment_status` in ('paid','partially_refunded','refunded') then `py`.`amount` else 0 end),0) AS `total_recorded_payments`,coalesce(sum(`py`.`refunded_amount`),0) AS `total_refunded` from (((((`bookings` `b` join `students` `s` on(`s`.`student_id` = `b`.`student_id`)) left join `parents` `p` on(`p`.`parent_id` = `b`.`parent_id`)) join `classes` `c` on(`c`.`class_id` = `b`.`class_id`)) join `courses` `co` on(`co`.`course_id` = `c`.`course_id`)) left join `payments` `py` on(`py`.`booking_id` = `b`.`booking_id`)) group by `b`.`booking_id`,`s`.`student_name`,`p`.`parent_name`,`c`.`class_code`,`co`.`course_name`,`b`.`price_at_booking` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Done. Suggested verification:
--
--   SELECT (SELECT COUNT(*) FROM users)        AS users,
--          (SELECT COUNT(*) FROM site_pages)   AS pages,
--          (SELECT COUNT(*) FROM page_sections) AS sections,
--          (SELECT COUNT(*) FROM cake_migrations) AS migrations;
--
-- Expected: users >= 3, pages = 4, sections >= 20, migrations >= 32.
-- =============================================================================
