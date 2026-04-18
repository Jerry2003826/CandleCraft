<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

class AdminSeed extends BaseSeed
{
    public function run(): void
    {
        $seedPassword = (string)(env('ADMIN_SEED_PASSWORD') ?: 'admin123');
        $hashedPassword = (new DefaultPasswordHasher())->hash($seedPassword);
        $now = date('Y-m-d H:i:s');

        $usersData = [
            [
                'username' => 'admin',
                'email' => 'admin@candlecraft.com',
                'password_hash' => $hashedPassword,
                'user_role' => 'admin',
                'account_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $usersTable = $this->table('users');
        $usersTable->insert($usersData)->save();

        $userId = $this->getAdapter()->getConnection()->lastInsertId();

        $adminsData = [
            [
                'user_id' => $userId,
                'admin_name' => 'System Administrator',
                'is_super_admin' => true,
                'notes' => 'Seeded admin account. Rotate credentials outside local demo environments.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $adminsTable = $this->table('admins');
        $adminsTable->insert($adminsData)->save();

        $sampleMessages = [
            [
                'sender_name' => 'Sarah Jones',
                'sender_email' => 'sarah.jones@example.com',
                'sender_phone' => '0400000001',
                'source_page' => 'homepage',
                'subject' => 'Enquiry about pottery classes',
                'message_text' => 'Hi, I would like to know more about your beginner pottery classes. What times are available and what is the cost?',
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'sent_at' => $now,
                'updated_at' => $now,
            ],
            [
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
            [
                'sender_name' => 'Bill Gates',
                'sender_email' => 'bill.gates@example.com',
                'sender_phone' => '0400000003',
                'source_page' => 'homepage',
                'subject' => 'Private lessons inquiry',
                'message_text' => 'I am interested in private pottery lessons for my daughter. Could you provide more information about pricing?',
                'message_type' => 'contact_form',
                'message_status' => 'read',
                'sent_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
        ];

        $messagesTable = $this->table('messages');
        $messagesTable->insert($sampleMessages)->save();
    }
}
