-- Academy Management Database (Improved Full Version)
-- Dialect: MySQL 8.0+ / MariaDB

CREATE DATABASE IF NOT EXISTS academy_management_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE academy_management_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS learning_resources;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS ai_interactions;
DROP TABLE IF EXISTS parent_students;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS parents;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username              VARCHAR(50) NOT NULL,
    email                 VARCHAR(255) NOT NULL,
    password_hash         VARCHAR(255) NOT NULL,
    user_role             ENUM('admin', 'teacher', 'student') NOT NULL,
    account_status        ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    age_verified_by_admin BOOLEAN NOT NULL DEFAULT FALSE,
    self_declared_adult   BOOLEAN NOT NULL DEFAULT FALSE,
    last_login_at         DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_users PRIMARY KEY (user_id),
    CONSTRAINT uk_users_username UNIQUE (username),
    CONSTRAINT uk_users_email UNIQUE (email),
    CONSTRAINT chk_users_username_length CHECK (CHAR_LENGTH(username) >= 3)
) ENGINE=InnoDB COMMENT='Central login table for all system accounts';

CREATE INDEX idx_users_role ON users (user_role);
CREATE INDEX idx_users_account_status ON users (account_status);

CREATE TABLE admins (
    admin_id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id               BIGINT UNSIGNED NOT NULL,
    admin_name            VARCHAR(100) NOT NULL,
    is_super_admin        BOOLEAN NOT NULL DEFAULT FALSE,
    notes                 TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_admins PRIMARY KEY (admin_id),
    CONSTRAINT uk_admins_user_id UNIQUE (user_id),
    CONSTRAINT fk_admins_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Administrator profile data';

CREATE TABLE parents (
    parent_id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id               BIGINT UNSIGNED NOT NULL,
    parent_name           VARCHAR(100) NOT NULL,
    phone_number          VARCHAR(30) NOT NULL,
    emergency_contact_name  VARCHAR(100) NULL,
    emergency_contact_phone VARCHAR(30) NULL,
    address               VARCHAR(255) NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_parents PRIMARY KEY (parent_id),
    CONSTRAINT uk_parents_user_id UNIQUE (user_id),
    CONSTRAINT fk_parents_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Parent profiles';

CREATE INDEX idx_parents_phone_number ON parents (phone_number);

CREATE TABLE students (
    student_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id               BIGINT UNSIGNED NULL,
    student_name          VARCHAR(100) NOT NULL,
    declared_age          TINYINT UNSIGNED NULL,
    date_of_birth         DATE NULL,
    student_status        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    medical_notes         TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_students PRIMARY KEY (student_id),
    CONSTRAINT uk_students_user_id UNIQUE (user_id),
    CONSTRAINT fk_students_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Student records; login is optional';

CREATE INDEX idx_students_name ON students (student_name);
CREATE INDEX idx_students_declared_age ON students (declared_age);
CREATE INDEX idx_students_status ON students (student_status);

CREATE TABLE teachers (
    teacher_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id               BIGINT UNSIGNED NOT NULL,
    teacher_name          VARCHAR(100) NOT NULL,
    phone_number          VARCHAR(30) NULL,
    specialization        VARCHAR(100) NULL,
    teacher_status        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    hire_date             DATE NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_teachers PRIMARY KEY (teacher_id),
    CONSTRAINT uk_teachers_user_id UNIQUE (user_id),
    CONSTRAINT fk_teachers_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Teacher profiles';

CREATE INDEX idx_teachers_name ON teachers (teacher_name);
CREATE INDEX idx_teachers_status ON teachers (teacher_status);

CREATE TABLE parent_students (
    parent_id             BIGINT UNSIGNED NOT NULL,
    student_id            BIGINT UNSIGNED NOT NULL,
    relationship_to_student VARCHAR(50) NOT NULL,
    is_primary_guardian   BOOLEAN NOT NULL DEFAULT FALSE,
    can_pick_up           BOOLEAN NOT NULL DEFAULT TRUE,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_parent_students PRIMARY KEY (parent_id, student_id),
    CONSTRAINT fk_parent_students_parent_id
        FOREIGN KEY (parent_id)
        REFERENCES parents (parent_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_parent_students_student_id
        FOREIGN KEY (student_id)
        REFERENCES students (student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Associates parents with one or more students';

CREATE INDEX idx_parent_students_student_id ON parent_students (student_id);
CREATE INDEX idx_parent_students_relationship ON parent_students (relationship_to_student);

CREATE TABLE courses (
    course_id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_name           VARCHAR(100) NOT NULL,
    course_type           VARCHAR(50) NOT NULL,
    course_level          ENUM('beginner', 'intermediate', 'advanced', 'all_levels') NOT NULL DEFAULT 'beginner',
    course_price          DECIMAL(10,2) NOT NULL,
    course_description    TEXT NULL,
    is_active             BOOLEAN NOT NULL DEFAULT TRUE,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_courses PRIMARY KEY (course_id),
    CONSTRAINT uk_courses_name_level UNIQUE (course_name, course_level),
    CONSTRAINT chk_courses_price_non_negative CHECK (course_price >= 0)
) ENGINE=InnoDB COMMENT='Course master data';

CREATE INDEX idx_courses_type ON courses (course_type);
CREATE INDEX idx_courses_level ON courses (course_level);
CREATE INDEX idx_courses_active ON courses (is_active);

CREATE TABLE classes (
    class_id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_code            VARCHAR(30) NOT NULL,
    course_id             BIGINT UNSIGNED NOT NULL,
    teacher_id            BIGINT UNSIGNED NOT NULL,
    start_datetime        DATETIME NOT NULL,
    end_datetime          DATETIME NOT NULL,
    location              VARCHAR(150) NOT NULL,
    capacity              INT UNSIGNED NOT NULL DEFAULT 20,
    class_status          ENUM('scheduled', 'ongoing', 'completed', 'cancelled', 'full') NOT NULL DEFAULT 'scheduled',
    notes                 TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_classes PRIMARY KEY (class_id),
    CONSTRAINT uk_classes_class_code UNIQUE (class_code),
    CONSTRAINT fk_classes_course_id
        FOREIGN KEY (course_id)
        REFERENCES courses (course_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_classes_teacher_id
        FOREIGN KEY (teacher_id)
        REFERENCES teachers (teacher_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_classes_capacity_positive CHECK (capacity > 0),
    CONSTRAINT chk_classes_time_order CHECK (end_datetime > start_datetime)
) ENGINE=InnoDB COMMENT='Scheduled class sessions';

CREATE INDEX idx_classes_course_id ON classes (course_id);
CREATE INDEX idx_classes_teacher_id ON classes (teacher_id);
CREATE INDEX idx_classes_start_datetime ON classes (start_datetime);
CREATE INDEX idx_classes_status ON classes (class_status);

CREATE TABLE learning_resources (
    resource_id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id              BIGINT UNSIGNED NOT NULL,
    uploaded_by_teacher_id BIGINT UNSIGNED NULL,
    resource_name         VARCHAR(150) NOT NULL,
    resource_type         ENUM('pdf', 'video', 'image', 'document', 'link', 'other') NOT NULL DEFAULT 'other',
    resource_url          VARCHAR(500) NULL,
    file_path             VARCHAR(255) NULL,
    resource_description  TEXT NULL,
    resource_status       ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    uploaded_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_learning_resources PRIMARY KEY (resource_id),
    CONSTRAINT fk_learning_resources_class_id
        FOREIGN KEY (class_id)
        REFERENCES classes (class_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_learning_resources_uploaded_by_teacher_id
        FOREIGN KEY (uploaded_by_teacher_id)
        REFERENCES teachers (teacher_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT chk_learning_resources_location CHECK (
        resource_url IS NOT NULL OR file_path IS NOT NULL
    )
) ENGINE=InnoDB COMMENT='Learning materials assigned to classes';

CREATE INDEX idx_learning_resources_class_id ON learning_resources (class_id);
CREATE INDEX idx_learning_resources_resource_status ON learning_resources (resource_status);
CREATE INDEX idx_learning_resources_resource_type ON learning_resources (resource_type);

CREATE TABLE bookings (
    booking_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id              BIGINT UNSIGNED NOT NULL,
    parent_id             BIGINT UNSIGNED NULL,
    student_id            BIGINT UNSIGNED NOT NULL,
    booking_date          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    booking_status        ENUM('pending', 'confirmed', 'cancelled', 'completed', 'waitlisted') NOT NULL DEFAULT 'pending',
    price_at_booking      DECIMAL(10,2) NOT NULL,
    notes                 TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_bookings PRIMARY KEY (booking_id),
    CONSTRAINT uk_bookings_class_student UNIQUE (class_id, student_id),
    CONSTRAINT fk_bookings_class_id
        FOREIGN KEY (class_id)
        REFERENCES classes (class_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_parent_student
        FOREIGN KEY (parent_id, student_id)
        REFERENCES parent_students (parent_id, student_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_bookings_price_non_negative CHECK (price_at_booking >= 0)
) ENGINE=InnoDB COMMENT='Class reservations made in the portal; parent linkage is optional legacy data';

CREATE INDEX idx_bookings_parent_id ON bookings (parent_id);
CREATE INDEX idx_bookings_student_id ON bookings (student_id);
CREATE INDEX idx_bookings_booking_status ON bookings (booking_status);
CREATE INDEX idx_bookings_booking_date ON bookings (booking_date);

CREATE TABLE payments (
    payment_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id            BIGINT UNSIGNED NOT NULL,
    amount                DECIMAL(10,2) NOT NULL,
    currency_code         CHAR(3) NOT NULL DEFAULT 'AUD',
    payment_date          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_method        ENUM('cash', 'card', 'bank_transfer', 'online', 'other') NOT NULL,
    payment_status        ENUM('pending', 'paid', 'failed', 'expired', 'voided', 'refund_required', 'refunded', 'partially_refunded') NOT NULL DEFAULT 'pending',
    transaction_reference VARCHAR(100) NULL,
    refunded_amount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes                 TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_payments PRIMARY KEY (payment_id),
    CONSTRAINT uk_payments_transaction_reference UNIQUE (transaction_reference),
    CONSTRAINT fk_payments_booking_id
        FOREIGN KEY (booking_id)
        REFERENCES bookings (booking_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_payments_amount_non_negative CHECK (amount >= 0),
    CONSTRAINT chk_payments_refunded_amount_range CHECK (
        refunded_amount >= 0 AND refunded_amount <= amount
    )
) ENGINE=InnoDB COMMENT='Payment transactions for bookings. Zero-amount bookings are confirmed locally and do not require Stripe checkout.';

CREATE INDEX idx_payments_booking_id ON payments (booking_id);
CREATE INDEX idx_payments_payment_status ON payments (payment_status);
CREATE INDEX idx_payments_payment_date ON payments (payment_date);
CREATE INDEX idx_payments_payment_method ON payments (payment_method);

CREATE TABLE attendance_records (
    attendance_id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id            BIGINT UNSIGNED NOT NULL,
    marked_by_teacher_id  BIGINT UNSIGNED NULL,
    attendance_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    attendance_status     ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
    attendance_notes      TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_attendance_records PRIMARY KEY (attendance_id),
    CONSTRAINT uk_attendance_records_booking_id UNIQUE (booking_id),
    CONSTRAINT fk_attendance_records_booking_id
        FOREIGN KEY (booking_id)
        REFERENCES bookings (booking_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_records_marked_by_teacher_id
        FOREIGN KEY (marked_by_teacher_id)
        REFERENCES teachers (teacher_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Attendance entries for booked classes';

CREATE INDEX idx_attendance_records_status ON attendance_records (attendance_status);
CREATE INDEX idx_attendance_records_date ON attendance_records (attendance_date);

CREATE TABLE messages (
    message_id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_user_id        BIGINT UNSIGNED NULL,
    receiver_user_id      BIGINT UNSIGNED NULL,
    sender_name           VARCHAR(100) NULL,
    sender_email          VARCHAR(255) NULL,
    sender_phone          VARCHAR(30) NULL,
    source_page           VARCHAR(255) NULL,
    subject               VARCHAR(150) NOT NULL,
    message_text          TEXT NOT NULL,
    message_type          ENUM('internal', 'contact_form') NOT NULL DEFAULT 'internal',
    message_status        ENUM('unread', 'read', 'replied', 'archived') NOT NULL DEFAULT 'unread',
    sent_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_messages PRIMARY KEY (message_id),
    CONSTRAINT fk_messages_sender_user_id
        FOREIGN KEY (sender_user_id)
        REFERENCES users (user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_messages_receiver_user_id
        FOREIGN KEY (receiver_user_id)
        REFERENCES users (user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT chk_messages_contact_form_sender_email CHECK (
        message_type <> 'contact_form' OR sender_email IS NOT NULL
    )
) ENGINE=InnoDB COMMENT='Internal messages and contact form submissions';

CREATE INDEX idx_messages_sender_user_id ON messages (sender_user_id);
CREATE INDEX idx_messages_receiver_user_id ON messages (receiver_user_id);
CREATE INDEX idx_messages_message_status ON messages (message_status);
CREATE INDEX idx_messages_sent_at ON messages (sent_at);
CREATE INDEX idx_messages_message_type ON messages (message_type);
CREATE INDEX idx_messages_source_page ON messages (source_page);

CREATE TABLE ai_interactions (
    interaction_id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id               BIGINT UNSIGNED NOT NULL,
    interaction_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    input_text            LONGTEXT NOT NULL,
    response_text         LONGTEXT NOT NULL,
    interaction_type      ENUM('chatbot', 'faq', 'recommendation', 'support', 'other') NOT NULL DEFAULT 'chatbot',

    CONSTRAINT pk_ai_interactions PRIMARY KEY (interaction_id),
    CONSTRAINT fk_ai_interactions_user_id
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='AI interaction logs';

CREATE INDEX idx_ai_interactions_user_id ON ai_interactions (user_id);
CREATE INDEX idx_ai_interactions_interaction_at ON ai_interactions (interaction_at);
CREATE INDEX idx_ai_interactions_interaction_type ON ai_interactions (interaction_type);

CREATE OR REPLACE VIEW vw_booking_details AS
SELECT
    b.booking_id,
    b.booking_date,
    b.booking_status,
    b.price_at_booking,
    s.student_id,
    s.student_name,
    p.parent_id,
    p.parent_name,
    c.class_id,
    c.class_code,
    c.start_datetime,
    c.end_datetime,
    c.location,
    c.class_status,
    co.course_id,
    co.course_name,
    co.course_level,
    t.teacher_id,
    t.teacher_name
FROM bookings b
JOIN students s ON s.student_id = b.student_id
LEFT JOIN parents p ON p.parent_id = b.parent_id
JOIN classes c ON c.class_id = b.class_id
JOIN courses co ON co.course_id = c.course_id
JOIN teachers t ON t.teacher_id = c.teacher_id;

CREATE OR REPLACE VIEW vw_payment_summary AS
SELECT
    b.booking_id,
    s.student_name,
    p.parent_name,
    c.class_code,
    co.course_name,
    b.price_at_booking,
    COALESCE(SUM(CASE WHEN py.payment_status IN ('paid', 'partially_refunded', 'refunded') THEN py.amount ELSE 0 END), 0) AS total_recorded_payments,
    COALESCE(SUM(py.refunded_amount), 0) AS total_refunded
FROM bookings b
JOIN students s ON s.student_id = b.student_id
LEFT JOIN parents p ON p.parent_id = b.parent_id
JOIN classes c ON c.class_id = b.class_id
JOIN courses co ON co.course_id = c.course_id
LEFT JOIN payments py ON py.booking_id = b.booking_id
GROUP BY
    b.booking_id,
    s.student_name,
    p.parent_name,
    c.class_code,
    co.course_name,
    b.price_at_booking;
