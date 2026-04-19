<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class MessagesControllerTest extends AppIntegrationTestCase
{
    public function testViewDisplaysCustomerAccessRequestMetadata(): void
    {
        $messageId = $this->createCustomerAccessMessage(false);

        $this->loginAsAdmin();
        $this->get('/admin/messages/view/' . $messageId);

        $this->assertResponseOk();
        $this->assertResponseContains('Requested access as');
        $this->assertResponseContains('Customer');
        $this->assertResponseContains('Legacy Profile');
        $this->assertResponseContains('Student');
    }

    public function testCreateAccountCarriesSelfDeclaredAdultFromCustomerAccessRequest(): void
    {
        $messageId = $this->createCustomerAccessMessage(true, 'new-customer@example.com');

        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/messages/create-account/' . $messageId, [
            'user_role' => 'student',
            'username' => 'newcustomer',
            'email' => 'new-customer@example.com',
            'password' => 'TempPass123!',
            'account_status' => 'active',
            'profile_name' => 'New Customer',
            'phone_number' => '0400000099',
            'declared_age' => 19,
            'date_of_birth' => '2007-01-01',
            'student_status' => 'active',
            'medical_notes' => '',
        ]);

        $this->assertResponseCode(302);

        $users = FactoryLocator::get('Table')->get('Users');
        $students = FactoryLocator::get('Table')->get('Students');
        $messages = FactoryLocator::get('Table')->get('Messages');

        $user = $users->find()
            ->select(['Users.user_id', 'Users.user_role', 'Users.self_declared_adult'])
            ->where(['Users.email' => 'new-customer@example.com'])
            ->disableHydration()
            ->firstOrFail();
        $student = $students->find()
            ->select(['Students.student_name'])
            ->where(['Students.user_id' => $user['user_id']])
            ->disableHydration()
            ->firstOrFail();
        $message = $messages->find()
            ->select(['Messages.message_status'])
            ->where(['Messages.message_id' => $messageId])
            ->disableHydration()
            ->firstOrFail();

        $this->assertSame('student', $user['user_role']);
        $this->assertTrue((bool)$user['self_declared_adult']);
        $this->assertSame('New Customer', $student['student_name']);
        $this->assertSame('replied', $message['message_status']);
    }

    private function createCustomerAccessMessage(bool $selfDeclaredAdult, string $email = 'customer-request@example.com'): int
    {
        $messages = FactoryLocator::get('Table')->get('Messages');
        $nextId = (int)$messages->find()->select(['message_id'])->orderBy(['Messages.message_id' => 'DESC'])->firstOrFail()->message_id + 1;
        $message = $messages->newEntity(
            [
                'message_id' => $nextId,
                'sender_name' => 'Customer Requester',
                'sender_email' => $email,
                'sender_phone' => '0400000011',
                'source_page' => 'account-request',
                'subject' => 'Customer portal request',
                'message_text' => implode("\n", [
                    '[REQUEST TYPE: customer_access]',
                    '[REQUESTED PORTAL: customer]',
                    '[LEGACY PROFILE TYPE: student]',
                    '[DECLARED AGE: 19]',
                    '[SELF DECLARED 18+: ' . ($selfDeclaredAdult ? 'yes' : 'no') . ']',
                    '',
                    'Please create a customer portal account for me.',
                ]),
                'message_type' => 'contact_form',
                'message_status' => 'unread',
                'sent_at' => '2026-04-20 10:00:00',
                'updated_at' => '2026-04-20 10:00:00',
            ],
            [
                'accessibleFields' => [
                    'message_id' => true,
                ],
            ]
        );
        $messages->saveOrFail($message);

        return (int)$message->message_id;
    }
}
