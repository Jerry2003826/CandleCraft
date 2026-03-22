-- ================================================
-- CandleCraft Academy - Complete Seed Data
-- Default admin login: admin@candlecraft.com / admin123
-- ================================================

USE academy_management_db;

-- ---- Admin User ----
INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('admin', 'admin@candlecraft.com',
        '$2y$12$akBG7iJyrZNNUhwuhiaQ1ebYH4L4M1NUqOeE/9uiTaMCFu7emsqrG',
        'admin', 'active', NOW(), NOW());

SET @admin_user_id = LAST_INSERT_ID();

INSERT INTO admins (user_id, admin_name, is_super_admin, notes, created_at, updated_at)
VALUES (@admin_user_id, 'System Administrator', TRUE, 'Default admin account', NOW(), NOW());

-- ---- Teacher Users ----
INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('emma.clay', 'emma.clay@candlecraft.com',
        '$2y$12$akBG7iJyrZNNUhwuhiaQ1ebYH4L4M1NUqOeE/9uiTaMCFu7emsqrG',
        'teacher', 'active', NOW(), NOW());
SET @teacher1_user_id = LAST_INSERT_ID();

INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('james.knit', 'james.knit@candlecraft.com',
        '$2y$12$akBG7iJyrZNNUhwuhiaQ1ebYH4L4M1NUqOeE/9uiTaMCFu7emsqrG',
        'teacher', 'active', NOW(), NOW());
SET @teacher2_user_id = LAST_INSERT_ID();

INSERT INTO teachers (user_id, teacher_name, phone_number, specialization, teacher_status, hire_date, created_at, updated_at)
VALUES
(@teacher1_user_id, 'Emma Clay', '0412345678', 'pottery', 'active', '2025-03-01', NOW(), NOW()),
(@teacher2_user_id, 'James Knit', '0498765432', 'knitting', 'active', '2025-06-15', NOW(), NOW());

SET @teacher1_id = (SELECT teacher_id FROM teachers WHERE user_id = @teacher1_user_id);
SET @teacher2_id = (SELECT teacher_id FROM teachers WHERE user_id = @teacher2_user_id);

-- ---- Parent Users ----
INSERT INTO users (username, email, password_hash, user_role, account_status, created_at, updated_at)
VALUES ('lisa.parent', 'lisa.parent@example.com',
        '$2y$12$akBG7iJyrZNNUhwuhiaQ1ebYH4L4M1NUqOeE/9uiTaMCFu7emsqrG',
        'parent', 'active', NOW(), NOW());
SET @parent1_user_id = LAST_INSERT_ID();

INSERT INTO parents (user_id, parent_name, phone_number, address, created_at, updated_at)
VALUES (@parent1_user_id, 'Lisa Wong', '0411111111', '123 Main St, Melbourne VIC 3000', NOW(), NOW());
SET @parent1_id = LAST_INSERT_ID();

-- ---- Students ----
INSERT INTO students (student_name, date_of_birth, student_status, created_at, updated_at)
VALUES
('Alice Wong', '2015-04-12', 'active', NOW(), NOW()),
('Bob Chen', '2014-09-25', 'active', NOW(), NOW()),
('Charlie Lee', '2016-01-08', 'active', NOW(), NOW());

SET @student1_id = (SELECT student_id FROM students WHERE student_name = 'Alice Wong');
SET @student2_id = (SELECT student_id FROM students WHERE student_name = 'Bob Chen');

INSERT INTO parent_students (parent_id, student_id, relationship_to_student, is_primary_guardian, can_pick_up, created_at)
VALUES (@parent1_id, @student1_id, 'Mother', TRUE, TRUE, NOW());

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
 'Explore complex patterns, colorwork, and garment construction.', TRUE, NOW(), NOW());

SET @course1_id = (SELECT course_id FROM courses WHERE course_name = 'Introduction to Pottery');
SET @course2_id = (SELECT course_id FROM courses WHERE course_name = 'Advanced Pottery Techniques');
SET @course3_id = (SELECT course_id FROM courses WHERE course_name = 'Beginner Knitting');
SET @course4_id = (SELECT course_id FROM courses WHERE course_name = 'Intermediate Knitting Patterns');

-- ---- Classes ----
INSERT INTO classes (class_code, course_id, teacher_id, start_datetime, end_datetime, location, capacity, class_status, created_at, updated_at)
VALUES
('POT-BEG-001', @course1_id, @teacher1_id, '2026-04-07 10:00:00', '2026-04-07 12:00:00', 'Studio A', 15, 'scheduled', NOW(), NOW()),
('POT-ADV-001', @course2_id, @teacher1_id, '2026-04-08 14:00:00', '2026-04-08 16:30:00', 'Studio A', 10, 'scheduled', NOW(), NOW()),
('KNT-BEG-001', @course3_id, @teacher2_id, '2026-04-09 09:00:00', '2026-04-09 11:00:00', 'Room B', 20, 'scheduled', NOW(), NOW()),
('KNT-INT-001', @course4_id, @teacher2_id, '2026-04-10 13:00:00', '2026-04-10 15:30:00', 'Room B', 12, 'ongoing', NOW(), NOW());

SET @class1_id = (SELECT class_id FROM classes WHERE class_code = 'POT-BEG-001');

-- ---- Bookings ----
INSERT INTO bookings (class_id, parent_id, student_id, booking_date, booking_status, price_at_booking, created_at, updated_at)
VALUES (@class1_id, @parent1_id, @student1_id, NOW(), 'confirmed', 150.00, NOW(), NOW());

-- ---- Messages ----
INSERT INTO messages (sender_name, sender_email, subject, message_text, message_type, message_status, sent_at, updated_at)
VALUES
('Sarah Jones', 'sarah.jones@example.com',
 'Enquiry about pottery classes',
 'Hi, I would like to know more about your beginner pottery classes. What times are available and what is the cost?',
 'contact_form', 'unread', NOW(), NOW()),

('John Smith', 'john.smith@example.com',
 'Knitting class availability',
 'Hello, are there any spots available in the intermediate knitting class starting next month?',
 'contact_form', 'replied', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

('Bill Gates', 'bill.gates@example.com',
 'Private lessons inquiry',
 'I am interested in private pottery lessons for my daughter. Could you provide more information about pricing?',
 'contact_form', 'read', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),

('Mary Johnson', 'mary.j@example.com',
 'Group booking discount',
 'We have a group of 8 students interested in the beginner pottery class. Do you offer any group discounts?',
 'contact_form', 'unread', DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW()),

('David Wilson', 'david.w@example.com',
 'Schedule change request',
 'Could we possibly move the Wednesday knitting class to Thursday afternoon? Several parents have requested this change.',
 'contact_form', 'unread', DATE_SUB(NOW(), INTERVAL 3 HOUR), NOW());
