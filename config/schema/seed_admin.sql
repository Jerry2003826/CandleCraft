-- ================================================
-- CandleCraft Academy - Complete Seed Data
-- Default password for all seeded accounts: admin123
-- Seeded logins:
--   admin@candlecraft.com        (admin)
--   emma.clay@candlecraft.com    (teacher)
--   james.knit@candlecraft.com   (teacher)
--   alice.wong@candlecraft.com   (student, adult verified)
--   milo.nguyen@candlecraft.com  (student, verification pending)
-- ================================================

USE academy_management_db;

SET @default_password_hash = '$2y$12$akBG7iJyrZNNUhwuhiaQ1ebYH4L4M1NUqOeE/9uiTaMCFu7emsqrG';

-- ---- Reset Existing Data ----
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE attendance_records;
TRUNCATE TABLE payments;
TRUNCATE TABLE bookings;
TRUNCATE TABLE learning_resources;
TRUNCATE TABLE messages;
TRUNCATE TABLE ai_interactions;
TRUNCATE TABLE parent_students;
TRUNCATE TABLE classes;
TRUNCATE TABLE courses;
TRUNCATE TABLE teachers;
TRUNCATE TABLE students;
TRUNCATE TABLE parents;
TRUNCATE TABLE admins;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---- Admin User ----
INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('admin', 'admin@candlecraft.com', @default_password_hash, 'admin', 'active', NOW(), NOW());

SET @admin_user_id = LAST_INSERT_ID();

INSERT INTO admins (user_id, admin_name, is_super_admin, notes, created_at, updated_at)
VALUES (@admin_user_id, 'System Administrator', TRUE, 'Default admin account', NOW(), NOW());

-- ---- Teacher Users ----
INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('emma.clay', 'emma.clay@candlecraft.com', @default_password_hash, 'teacher', 'active', NOW(), NOW());
SET @teacher1_user_id = LAST_INSERT_ID();

INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('james.knit', 'james.knit@candlecraft.com', @default_password_hash, 'teacher', 'active', NOW(), NOW());
SET @teacher2_user_id = LAST_INSERT_ID();

INSERT INTO teachers (user_id, teacher_name, phone_number, specialization, teacher_status, hire_date, created_at, updated_at)
VALUES
(@teacher1_user_id, 'Emma Clay', '0412345678', 'pottery', 'active', DATE_SUB(CURDATE(), INTERVAL 13 MONTH), NOW(), NOW()),
(@teacher2_user_id, 'James Knit', '0498765432', 'knitting', 'active', DATE_SUB(CURDATE(), INTERVAL 10 MONTH), NOW(), NOW());

SET @teacher1_id = (SELECT teacher_id FROM teachers WHERE user_id = @teacher1_user_id);
SET @teacher2_id = (SELECT teacher_id FROM teachers WHERE user_id = @teacher2_user_id);

-- ---- Student Users ----
INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('alice.wong', 'alice.wong@candlecraft.com', @default_password_hash, 'student', 'active', NOW(), NOW());
SET @student1_user_id = LAST_INSERT_ID();

INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('milo.nguyen', 'milo.nguyen@candlecraft.com', @default_password_hash, 'student', 'active', NOW(), NOW());
SET @student2_user_id = LAST_INSERT_ID();

UPDATE users
SET age_verified_by_admin = TRUE
WHERE user_id = @student1_user_id;

-- ---- Students ----
INSERT INTO students (user_id, student_name, declared_age, date_of_birth, student_status, medical_notes, created_at, updated_at)
VALUES
(@student1_user_id, 'Alice Wong', 24, '2002-04-12', 'active', NULL, NOW(), NOW()),
(@student2_user_id, 'Milo Nguyen', 16, NULL, 'active', 'Adult verification pending. Can browse courses but cannot book or pay yet.', NOW(), NOW()),
(NULL, 'Bob Chen', 17, NULL, 'active', NULL, NOW(), NOW()),
(NULL, 'Charlie Lee', 14, NULL, 'active', 'Please seat near the front due to mild hearing difficulty.', NOW(), NOW());

SET @student1_id = (SELECT student_id FROM students WHERE student_name = 'Alice Wong');
SET @student2_id = (SELECT student_id FROM students WHERE student_name = 'Milo Nguyen');
SET @student3_id = (SELECT student_id FROM students WHERE student_name = 'Bob Chen');
SET @student4_id = (SELECT student_id FROM students WHERE student_name = 'Charlie Lee');

-- ---- Courses ----
INSERT INTO courses (course_name, course_type, course_level, course_price, course_description, is_active, created_at, updated_at)
VALUES
('Introduction to Pottery', 'pottery', 'beginner', 150.00,
 'Learn the basics of pottery including wheel throwing and hand building techniques.', TRUE, NOW(), NOW()),
('Advanced Pottery Techniques', 'pottery', 'advanced', 250.00,
 'Master advanced glazing, firing techniques, and complex forms.', TRUE, NOW(), NOW()),
('Beginner Knitting', 'knitting', 'beginner', 120.00,
 'Start your knitting journey with basic stitches and simple projects.', TRUE, NOW(), NOW()),
('Intermediate Knitting Patterns', 'knitting', 'intermediate', 180.00,
 'Explore complex patterns, colourwork, and garment construction.', TRUE, NOW(), NOW());

SET @course1_id = (SELECT course_id FROM courses WHERE course_name = 'Introduction to Pottery');
SET @course2_id = (SELECT course_id FROM courses WHERE course_name = 'Advanced Pottery Techniques');
SET @course3_id = (SELECT course_id FROM courses WHERE course_name = 'Beginner Knitting');
SET @course4_id = (SELECT course_id FROM courses WHERE course_name = 'Intermediate Knitting Patterns');

-- ---- Classes ----
INSERT INTO classes (class_code, course_id, teacher_id, start_datetime, end_datetime, location, capacity, class_status, notes, created_at, updated_at)
VALUES
('POT-BEG-001', @course1_id, @teacher1_id,
 TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00'),
 TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:00:00'),
 'Studio A', 15, 'scheduled', 'Great first class for brand new students.', NOW(), NOW()),
('POT-ADV-001', @course2_id, @teacher1_id,
 TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 5 DAY), '14:00:00'),
 TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 5 DAY), '16:30:00'),
 'Studio A', 10, 'scheduled', 'Includes glazing demonstration.', NOW(), NOW()),
('KNT-BEG-001', @course3_id, @teacher2_id,
 TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00'),
 TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '11:00:00'),
 'Room B', 20, 'completed', 'Last week beginner workshop.', NOW(), NOW()),
('KNT-INT-001', @course4_id, @teacher2_id,
 TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 4 DAY), '13:00:00'),
 TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 4 DAY), '15:30:00'),
 'Room B', 12, 'scheduled', 'Pattern reading and garment shaping focus.', NOW(), NOW());

SET @class1_id = (SELECT class_id FROM classes WHERE class_code = 'POT-BEG-001');
SET @class2_id = (SELECT class_id FROM classes WHERE class_code = 'POT-ADV-001');
SET @class3_id = (SELECT class_id FROM classes WHERE class_code = 'KNT-BEG-001');
SET @class4_id = (SELECT class_id FROM classes WHERE class_code = 'KNT-INT-001');

-- ---- Learning Resources ----
INSERT INTO learning_resources (
    class_id,
    uploaded_by_teacher_id,
    resource_name,
    resource_type,
    resource_url,
    resource_description,
    resource_status,
    uploaded_at
)
VALUES
(@class1_id, @teacher1_id, 'Pottery Prep Guide', 'pdf',
 'https://example.com/resources/pottery-prep-guide.pdf',
 'Starter guide covering apron, clay prep, and studio etiquette.', 'active', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(@class3_id, @teacher2_id, 'Beginner Knitting Stitch Reference', 'link',
 'https://example.com/resources/knitting-stitch-reference',
 'Reference sheet for the core stitches covered in the class.', 'active', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(@class2_id, @teacher1_id, 'Advanced Glazing Notes', 'document',
 'https://example.com/resources/advanced-glazing-notes',
 'Shared after the advanced workshop starts.', 'active', DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- ---- Bookings ----
INSERT INTO bookings (class_id, parent_id, student_id, booking_date, booking_status, price_at_booking, notes, created_at, updated_at)
VALUES
(@class1_id, NULL, @student1_id, DATE_SUB(NOW(), INTERVAL 1 DAY), 'pending', 150.00, 'Adult verified student can complete payment in the portal.', NOW(), NOW()),
(@class3_id, NULL, @student1_id, DATE_SUB(NOW(), INTERVAL 9 DAY), 'completed', 120.00, 'Completed class used for attendance/resource examples.', NOW(), NOW()),
(@class2_id, NULL, @student3_id, DATE_SUB(NOW(), INTERVAL 8 HOUR), 'confirmed', 250.00, 'Admin-entered booking for roster demonstration.', NOW(), NOW()),
(@class4_id, NULL, @student4_id, DATE_SUB(NOW(), INTERVAL 3 HOUR), 'pending', 180.00, 'Shows a second student on the upcoming knitting class.', NOW(), NOW());

SET @alice_upcoming_booking_id = (
    SELECT booking_id
    FROM bookings
    WHERE class_id = @class1_id AND student_id = @student1_id
);
SET @alice_completed_booking_id = (
    SELECT booking_id
    FROM bookings
    WHERE class_id = @class3_id AND student_id = @student1_id
);

-- ---- Payments ----
INSERT INTO payments (
    booking_id,
    amount,
    currency_code,
    payment_date,
    payment_method,
    payment_status,
    transaction_reference,
    refunded_amount,
    notes,
    created_at,
    updated_at
)
VALUES
(@alice_completed_booking_id, 120.00, 'AUD', DATE_SUB(NOW(), INTERVAL 7 DAY), 'card', 'paid', 'SEED-PAID-ALICE-KNT-001', 0.00,
 'Completed seed payment for the past knitting workshop.', NOW(), NOW());

-- ---- Attendance ----
INSERT INTO attendance_records (
    booking_id,
    marked_by_teacher_id,
    attendance_date,
    attendance_status,
    attendance_notes,
    created_at,
    updated_at
)
VALUES
(@alice_completed_booking_id, @teacher2_id, DATE_SUB(NOW(), INTERVAL 7 DAY), 'present',
 'Participated confidently and completed the scarf sampler.', NOW(), NOW());

-- ---- Messages ----
INSERT INTO messages (sender_name, sender_email, sender_phone, source_page, subject, message_text, message_type, message_status, sent_at, updated_at)
VALUES
('Ava Park', 'ava.park@example.com',
 '0400000000', 'account-request',
 'Customer portal request',
 '[REQUEST TYPE: customer_access]\n[REQUESTED PORTAL: customer]\n[LEGACY PROFILE TYPE: student]\n[DECLARED AGE: 17]\n[SELF DECLARED 18+: no]\n\nI would like to join the beginner pottery class and need a portal login before I can book.',
 'contact_form', 'unread', DATE_SUB(NOW(), INTERVAL 40 MINUTE), DATE_SUB(NOW(), INTERVAL 40 MINUTE)),

('Sarah Jones', 'sarah.jones@example.com',
 '0400000001', 'homepage',
 'Enquiry about pottery classes',
 'Hi, I would like to know more about your beginner pottery classes. What times are available and what is the cost?',
 'contact_form', 'unread', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),

('John Smith', 'john.smith@example.com',
 '0400000002', 'homepage',
 'Knitting class availability',
 'Hello, are there any spots available in the intermediate knitting class starting next month?',
 'contact_form', 'replied', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

('Bill Gates', 'bill.gates@example.com',
 '0400000003', 'homepage',
 'Private lessons inquiry',
 'I am interested in private pottery lessons for my teenage son. Could you provide more information about pricing?',
 'contact_form', 'read', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),

('Mary Johnson', 'mary.j@example.com',
 '0400000004', 'homepage',
 'Group booking discount',
 'We have a group of 8 students interested in the beginner pottery class. Do you offer any group discounts?',
 'contact_form', 'unread', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR)),

('David Wilson', 'david.w@example.com',
 '0400000005', 'homepage',
 'Schedule change request',
 'Could we possibly move the Wednesday knitting class to Thursday afternoon? Several students have requested this change.',
 'contact_form', 'unread', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR));
