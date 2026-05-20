<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\EmailTrait;

class MessagesControllerTest extends AppIntegrationTestCase
{
    use EmailTrait;

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

    public function testViewLinksExistingCustomerRoleToLegacyStudentProfile(): void
    {
        $this->setUserRole(4, 'customer');
        $messageId = $this->createCustomerAccessMessage(false, 'student-one@candlecraft.com');

        $this->loginAsAdmin();
        $this->get('/admin/messages/view/' . $messageId);

        $this->assertResponseOk();
        $this->assertResponseContains('View Customer Record');
    }

    public function testArchiveMovesEnquiryToArchivedStatus(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/messages/archive/1');

        $this->assertResponseCode(302);

        $message = FactoryLocator::get('Table')->get('Messages')->get(1);
        $this->assertSame('archived', $message->message_status);
    }

    public function testDeleteRequiresArchivedStatus(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/messages/delete/1');

        $this->assertResponseCode(302);

        $messages = FactoryLocator::get('Table')->get('Messages');
        $message = $messages->get(1);
        $this->assertSame('unread', $message->message_status);
        $this->assertSame(1, $messages->find()->count());
    }

    public function testRestoreReturnsArchivedEnquiryToReadStatus(): void
    {
        $messages = FactoryLocator::get('Table')->get('Messages');
        $message = $messages->get(1);
        $message->message_status = 'archived';
        $messages->saveOrFail($message);

        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/messages/restore/1');

        $this->assertResponseCode(302);

        $message = $messages->get(1);
        $this->assertSame('read', $message->message_status);
    }

    public function testReplyBackLinkDoesNotDoublePrefixProductionReturnUrl(): void
    {
        $this->loginAsAdmin();
        $this->configRequest([
            'environment' => [
                'PHP_SELF' => '/production/index.php',
                'SCRIPT_NAME' => '/production/index.php',
            ],
        ]);

        $this->get('/admin/messages/reply/1?return_url=%2Fproduction%2Fadmin%2Fmessages%2Fview%2F1');

        $this->assertResponseOk();
        $this->assertResponseContains('href="/production/admin/messages/view/1"');
        $this->assertResponseNotContains('/production/production/admin/messages/view/1');
    }

    public function testReplyRedirectPrefixesProductionBasePathForAppRelativeReturnUrl(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->configRequest([
            'environment' => [
                'PHP_SELF' => '/production/index.php',
                'SCRIPT_NAME' => '/production/index.php',
            ],
        ]);

        $this->post('/admin/messages/reply/1', [
            'message_text' => 'Thanks, we will follow up shortly.',
            'return_url' => '/admin/messages/view/1',
        ]);

        $this->assertRedirect('/production/admin/messages/view/1');
        $this->assertMailCount(1);
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
            ],
        );
        $messages->saveOrFail($message);

        return (int)$message->message_id;
    }
}
