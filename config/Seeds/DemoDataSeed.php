<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

class DemoDataSeed extends BaseSeed
{
    /**
     * Core application tables that contain user-facing demo data.
     *
     * Resetting these tables keeps the demo environment deterministic without
     * touching migration metadata.
     *
     * @var list<string>
     */
    private const RESETTABLE_TABLES = [
        'attendance_records',
        'payments',
        'bookings',
        'learning_resources',
        'messages',
        'ai_interactions',
        'parent_students',
        'classes',
        'courses',
        'teachers',
        'students',
        'parents',
        'admins',
        'users',
    ];

    public function run(): void
    {
        $resetExisting = $this->isTruthy((string)(env('DEMO_SEED_RESET_EXISTING') ?: 'false'));
        $seedPassword = (string)(env('DEMO_SEED_PASSWORD') ?: env('ADMIN_SEED_PASSWORD') ?: 'admin123');
        $hashedPassword = (new DefaultPasswordHasher())->hash($seedPassword);
        $now = date('Y-m-d H:i:s');

        if ($this->hasExistingDemoSensitiveData()) {
            if (!$resetExisting) {
                throw new RuntimeException(
                    'DemoDataSeed found existing application data. ' .
                    'Run again with DEMO_SEED_RESET_EXISTING=true if you want to replace the current demo data.'
                );
            }

            $this->truncateDemoTables();
        }

        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'admin',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'emma.clay',
                'email' => 'emma.clay@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'teacher',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'james.knit',
                'email' => 'james.knit@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'teacher',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'alice.wong',
                'email' => 'alice.wong@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'student',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'ava.park',
                'email' => 'ava.park@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'customer',
                'account_status' => 'active',
                'age_verified_by_admin' => false,
                'self_declared_adult' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'olivia.lee',
                'email' => 'olivia.lee@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'parent',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->insert('users', $users);

        $userIds = $this->idMap('users', 'email', array_column($users, 'email'), 'user_id');

        $this->insert('admins', [[
            'user_id' => $userIds['admin@candlecraft.com'],
            'admin_name' => 'System Administrator',
            'is_super_admin' => true,
            'notes' => 'Demo admin account. Rotate or remove outside demo environments.',
            'created_at' => $now,
            'updated_at' => $now,
        ]]);

        $this->insert('teachers', [
            [
                'user_id' => $userIds['emma.clay@candlecraft.com'],
                'teacher_name' => 'Emma Clay',
                'phone_number' => '0412345678',
                'specialization' => 'pottery',
                'teacher_status' => 'active',
                'hire_date' => date('Y-m-d', strtotime('-13 months')),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userIds['james.knit@candlecraft.com'],
                'teacher_name' => 'James Knit',
                'phone_number' => '0498765432',
                'specialization' => 'knitting',
                'teacher_status' => 'active',
                'hire_date' => date('Y-m-d', strtotime('-10 months')),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $teacherIdsByUserId = $this->idMap('teachers', 'user_id', [
            (string)$userIds['emma.clay@candlecraft.com'],
            (string)$userIds['james.knit@candlecraft.com'],
        ], 'teacher_id');

        $this->insert('parents', [[
            'user_id' => $userIds['olivia.lee@candlecraft.com'],
            'parent_name' => 'Olivia Lee',
            'phone_number' => '0400111222',
            'emergency_contact_name' => 'Marcus Lee',
            'emergency_contact_phone' => '0400333444',
            'address' => '15 Garden Lane, Melbourne VIC',
            'created_at' => $now,
            'updated_at' => $now,
        ]]);

        $parentIds = $this->idMap('parents', 'user_id', [
            (string)$userIds['olivia.lee@candlecraft.com'],
        ], 'parent_id');

        $students = [
            [
                'user_id' => $userIds['alice.wong@candlecraft.com'],
                'student_name' => 'Alice Wong',
                'declared_age' => 24,
                'date_of_birth' => '2002-04-12',
                'student_status' => 'active',
                'medical_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userIds['ava.park@candlecraft.com'],
                'student_name' => 'Ava Park',
                'declared_age' => 17,
                'date_of_birth' => null,
                'student_status' => 'active',
                'medical_notes' => 'Adult verification pending. Can browse courses but cannot book or pay yet.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => null,
                'student_name' => 'Bob Chen',
                'declared_age' => 17,
                'date_of_birth' => null,
                'student_status' => 'active',
                'medical_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => null,
                'student_name' => 'Charlie Lee',
                'declared_age' => 14,
                'date_of_birth' => null,
                'student_status' => 'active',
                'medical_notes' => 'Please seat near the front due to mild hearing difficulty.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->insert('students', $students);

        $studentIds = $this->idMap('students', 'student_name', array_column($students, 'student_name'), 'student_id');

        $this->insert('parent_students', [[
            'parent_id' => $parentIds[(string)$userIds['olivia.lee@candlecraft.com']],
            'student_id' => $studentIds['Charlie Lee'],
            'relationship_to_student' => 'mother',
            'is_primary_guardian' => true,
            'can_pick_up' => true,
            'created_at' => $now,
        ]]);

        $courses = [
            [
                'course_name' => 'Introduction to Pottery',
                'course_type' => 'pottery',
                'course_level' => 'beginner',
                'course_price' => 150.00,
                'course_description' => 'Learn the basics of pottery including wheel throwing and hand building techniques.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'course_name' => 'Advanced Pottery Techniques',
                'course_type' => 'pottery',
                'course_level' => 'advanced',
                'course_price' => 250.00,
                'course_description' => 'Master advanced glazing, firing techniques, and complex forms.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'course_name' => 'Beginner Knitting',
                'course_type' => 'knitting',
                'course_level' => 'beginner',
                'course_price' => 120.00,
                'course_description' => 'Start your knitting journey with basic stitches and simple projects.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'course_name' => 'Intermediate Knitting Patterns',
                'course_type' => 'knitting',
                'course_level' => 'intermediate',
                'course_price' => 180.00,
                'course_description' => 'Explore complex patterns, colourwork, and garment construction.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->insert('courses', $courses);

        $courseIds = $this->idMap('courses', 'course_name', array_column($courses, 'course_name'), 'course_id');

        $classes = [
            [
                'class_code' => 'POT-BEG-001',
                'course_id' => $courseIds['Introduction to Pottery'],
                'teacher_id' => $teacherIdsByUserId[(string)$userIds['emma.clay@candlecraft.com']],
                'start_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 10:00')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 12:00')),
                'location' => 'Studio A',
                'capacity' => 15,
                'class_status' => 'scheduled',
                'notes' => 'Great first class for brand new students.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'class_code' => 'POT-ADV-001',
                'course_id' => $courseIds['Advanced Pottery Techniques'],
                'teacher_id' => $teacherIdsByUserId[(string)$userIds['emma.clay@candlecraft.com']],
                'start_datetime' => date('Y-m-d H:i:s', strtotime('+5 days 14:00')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('+5 days 16:30')),
                'location' => 'Studio A',
                'capacity' => 10,
                'class_status' => 'scheduled',
                'notes' => 'Includes glazing demonstration.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'class_code' => 'KNT-BEG-001',
                'course_id' => $courseIds['Beginner Knitting'],
                'teacher_id' => $teacherIdsByUserId[(string)$userIds['james.knit@candlecraft.com']],
                'start_datetime' => date('Y-m-d H:i:s', strtotime('-7 days 09:00')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('-7 days 11:00')),
                'location' => 'Room B',
                'capacity' => 20,
                'class_status' => 'completed',
                'notes' => 'Last week beginner workshop.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'class_code' => 'KNT-INT-001',
                'course_id' => $courseIds['Intermediate Knitting Patterns'],
                'teacher_id' => $teacherIdsByUserId[(string)$userIds['james.knit@candlecraft.com']],
                'start_datetime' => date('Y-m-d H:i:s', strtotime('+4 days 13:00')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('+4 days 15:30')),
                'location' => 'Room B',
                'capacity' => 12,
                'class_status' => 'scheduled',
                'notes' => 'Pattern reading and garment shaping focus.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->insert('classes', $classes);

        $classIds = $this->idMap('classes', 'class_code', array_column($classes, 'class_code'), 'class_id');

        $this->insert('learning_resources', [
            [
                'class_id' => $classIds['POT-BEG-001'],
                'uploaded_by_teacher_id' => $teacherIdsByUserId[(string)$userIds['emma.clay@candlecraft.com']],
                'resource_name' => 'Pottery Prep Guide',
                'resource_type' => 'pdf',
                'resource_url' => 'https://example.com/resources/pottery-prep-guide.pdf',
                'file_path' => null,
                'resource_description' => 'Starter guide covering apron, clay prep, and studio etiquette.',
                'resource_status' => 'active',
                'uploaded_at' => date('Y-m-d H:i:s', strtotime('-6 hours')),
            ],
            [
                'class_id' => $classIds['KNT-BEG-001'],
                'uploaded_by_teacher_id' => $teacherIdsByUserId[(string)$userIds['james.knit@candlecraft.com']],
                'resource_name' => 'Beginner Knitting Stitch Reference',
                'resource_type' => 'link',
                'resource_url' => 'https://example.com/resources/knitting-stitch-reference',
                'file_path' => null,
                'resource_description' => 'Reference sheet for the core stitches covered in the class.',
                'resource_status' => 'active',
                'uploaded_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
            ],
            [
                'class_id' => $classIds['POT-ADV-001'],
                'uploaded_by_teacher_id' => $teacherIdsByUserId[(string)$userIds['emma.clay@candlecraft.com']],
                'resource_name' => 'Advanced Glazing Notes',
                'resource_type' => 'document',
                'resource_url' => 'https://example.com/resources/advanced-glazing-notes',
                'file_path' => null,
                'resource_description' => 'Shared after the advanced workshop starts.',
                'resource_status' => 'active',
                'uploaded_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
        ]);

        $bookings = [
            [
                'class_id' => $classIds['POT-BEG-001'],
                'parent_id' => null,
                'student_id' => $studentIds['Alice Wong'],
                'booking_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'booking_status' => 'pending',
                'price_at_booking' => 150.00,
                'notes' => 'Adult verified customer can complete payment in the portal.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'class_id' => $classIds['KNT-BEG-001'],
                'parent_id' => null,
                'student_id' => $studentIds['Alice Wong'],
                'booking_date' => date('Y-m-d H:i:s', strtotime('-9 days')),
                'booking_status' => 'completed',
                'price_at_booking' => 120.00,
                'notes' => 'Completed class used for attendance and resource examples.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'class_id' => $classIds['POT-ADV-001'],
                'parent_id' => null,
                'student_id' => $studentIds['Bob Chen'],
                'booking_date' => date('Y-m-d H:i:s', strtotime('-8 hours')),
                'booking_status' => 'confirmed',
                'price_at_booking' => 250.00,
                'notes' => 'Admin-entered booking for roster demonstration.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'class_id' => $classIds['KNT-INT-001'],
                'parent_id' => $parentIds[(string)$userIds['olivia.lee@candlecraft.com']],
                'student_id' => $studentIds['Charlie Lee'],
                'booking_date' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                'booking_status' => 'pending',
                'price_at_booking' => 180.00,
                'notes' => 'Parent-managed booking for the family portal flow.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->insert('bookings', $bookings);

        $bookingIds = $this->bookingIdMap([
            [
                'key' => 'alice_upcoming',
                'class_id' => $classIds['POT-BEG-001'],
                'student_id' => $studentIds['Alice Wong'],
            ],
            [
                'key' => 'alice_completed',
                'class_id' => $classIds['KNT-BEG-001'],
                'student_id' => $studentIds['Alice Wong'],
            ],
            [
                'key' => 'charlie_parent_pending',
                'class_id' => $classIds['KNT-INT-001'],
                'student_id' => $studentIds['Charlie Lee'],
            ],
        ]);

        $this->insert('payments', [[
            'booking_id' => $bookingIds['alice_completed'],
            'amount' => 120.00,
            'currency_code' => 'AUD',
            'payment_date' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'transaction_reference' => 'SEED-PAID-ALICE-KNT-001',
            'refunded_amount' => 0.00,
            'notes' => 'Completed demo payment for the past knitting workshop.',
            'created_at' => $now,
            'updated_at' => $now,
        ]]);

        $this->insert('attendance_records', [[
            'booking_id' => $bookingIds['alice_completed'],
            'marked_by_teacher_id' => $teacherIdsByUserId[(string)$userIds['james.knit@candlecraft.com']],
            'attendance_date' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'attendance_status' => 'present',
            'attendance_notes' => 'Participated confidently and completed the scarf sampler.',
            'created_at' => $now,
            'updated_at' => $now,
        ]]);

        $this->insert('messages', [
            [
                'sender_user_id' => null,
                'receiver_user_id' => null,
                'sender_name' => 'Ava Park',
                'sender_email' => 'ava.park@example.com',
                'sender_phone' => '0400000000',
                'source_page' => 'account-request',
                'subject' => 'Customer portal request',
                'message_text' => "[REQUEST TYPE: customer_access]\n[REQUESTED PORTAL: customer]\n[LEGACY PROFILE TYPE: student]\n[DECLARED AGE: 17]\n[SELF DECLARED 18+: no]\n\nI would like to join the beginner pottery class and need a portal login before I can book.",
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'sent_at' => date('Y-m-d H:i:s', strtotime('-40 minutes')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-40 minutes')),
            ],
            [
                'sender_user_id' => null,
                'receiver_user_id' => null,
                'sender_name' => 'Sarah Jones',
                'sender_email' => 'sarah.jones@example.com',
                'sender_phone' => '0400000001',
                'source_page' => 'homepage',
                'subject' => 'Enquiry about pottery classes',
                'message_text' => 'Hi, I would like to know more about your beginner pottery classes. What times are available and what is the cost?',
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'sent_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
            [
                'sender_user_id' => null,
                'receiver_user_id' => null,
                'sender_name' => 'John Smith',
                'sender_email' => 'john.smith@example.com',
                'sender_phone' => '0400000002',
                'source_page' => 'homepage',
                'subject' => 'Knitting class availability',
                'message_text' => 'Hello, are there any spots available in the intermediate knitting class starting next month?',
                'message_type' => 'contact_form',
                'message_status' => 'replied',
                'sent_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
        ]);
    }

    private function hasExistingDemoSensitiveData(): bool
    {
        foreach (['users', 'students', 'teachers', 'parents', 'courses', 'classes', 'bookings'] as $table) {
            $row = $this->fetchRow(sprintf('SELECT COUNT(*) AS total FROM `%s`', $table));
            if ((int)($row['total'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function truncateDemoTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (self::RESETTABLE_TABLES as $table) {
                $this->execute(sprintf('TRUNCATE TABLE `%s`', $table));
            }
        } finally {
            $this->execute('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * @param list<string> $lookupValues
     * @return array<string, int>
     */
    private function idMap(string $table, string $keyColumn, array $lookupValues, string $idColumn): array
    {
        $sql = sprintf(
            'SELECT `%s`, `%s` FROM `%s` WHERE `%s` IN (%s)',
            $keyColumn,
            $idColumn,
            $table,
            $keyColumn,
            $this->sqlStringList($lookupValues)
        );
        $rows = $this->fetchAll($sql);
        $map = [];
        foreach ($rows as $row) {
            $map[(string)$row[$keyColumn]] = (int)$row[$idColumn];
        }

        return $map;
    }

    /**
     * @param list<array{key:string,class_id:int,student_id:int}> $pairs
     * @return array<string, int>
     */
    private function bookingIdMap(array $pairs): array
    {
        $map = [];
        foreach ($pairs as $pair) {
            $row = $this->fetchRow(sprintf(
                'SELECT `booking_id` FROM `bookings` WHERE `class_id` = %d AND `student_id` = %d ORDER BY `booking_id` DESC LIMIT 1',
                $pair['class_id'],
                $pair['student_id']
            ));

            if ($row === false) {
                throw new RuntimeException(sprintf(
                    'Could not resolve seeded booking for class_id=%d student_id=%d.',
                    $pair['class_id'],
                    $pair['student_id']
                ));
            }

            $map[$pair['key']] = (int)$row['booking_id'];
        }

        return $map;
    }

    /**
     * @param list<string> $values
     */
    private function sqlStringList(array $values): string
    {
        return implode(', ', array_map(
            fn(string $value): string => "'" . str_replace("'", "''", $value) . "'",
            $values
        ));
    }

    private function isTruthy(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
