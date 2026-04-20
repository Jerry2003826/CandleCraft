<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class PagesControllerCaptchaTest extends AppIntegrationTestCase
{
    public function testValidContactEnquiryWithTestRecaptchaAppearsInAdmin(): void
    {
        $messagesTable = FactoryLocator::get('Table')->get('Messages');
        $before = $messagesTable->find()->count();

        $this->enableCsrfToken();
        $this->post('/contact', [
            'sender_name' => 'Fresh Enquiry',
            'sender_email' => 'fresh-enquiry@example.com',
            'sender_phone' => '0400001234',
            'subject' => 'General enquiry',
            'message_text' => 'Can I book a pottery trial session?',
            'source_page' => 'homepage',
            'g-recaptcha-response' => 'test-token',
            'website' => '',
        ]);

        $this->assertResponseCode(302);
        $this->assertSame($before + 1, $messagesTable->find()->count());

        $message = $messagesTable->find()
            ->select(['message_type', 'message_status', 'source_page'])
            ->where(['Messages.sender_email' => 'fresh-enquiry@example.com'])
            ->orderBy(['Messages.message_id' => 'DESC'])
            ->disableHydration()
            ->firstOrFail();

        $this->assertSame('contact_form', $message['message_type']);
        $this->assertSame('unread', $message['message_status']);
        $this->assertSame('homepage', $message['source_page']);

        $this->loginAsAdmin();
        $this->get('/admin/messages');

        $this->assertResponseOk();
        $this->assertResponseContains('Fresh Enquiry');
        $this->assertResponseContains('General enquiry');
    }

    public function testMissingRecaptchaConfigStillAllowsMessageSave(): void
    {
        Configure::write('Recaptcha.site_key', '');
        Configure::write('Recaptcha.secret_key', '');
        $messagesTable = FactoryLocator::get('Table')->get('Messages');
        $before = $messagesTable->find()->count();

        $this->enableCsrfToken();
        $this->post('/contact', [
            'sender_name' => 'Test Sender',
            'sender_email' => 'sender@example.com',
            'sender_phone' => '0400000000',
            'subject' => 'General enquiry',
            'message_text' => 'Please tell me more.',
            'source_page' => 'contact',
            'g-recaptcha-response' => 'invalid-token',
            'website' => '',
        ]);

        $after = $messagesTable->find()->count();

        $this->assertResponseCode(302);
        $this->assertSame($before + 1, $after);
    }

    public function testCustomerAccessRequestRejectsConflictingAgeDeclaration(): void
    {
        Configure::write('Recaptcha.secret_key', '');
        $messagesTable = FactoryLocator::get('Table')->get('Messages');
        $before = $messagesTable->find()->count();

        $this->enableCsrfToken();
        $this->post('/contact', [
            'sender_name' => 'Conflicted Customer',
            'sender_email' => 'conflict@example.com',
            'sender_phone' => '0400000000',
            'subject' => 'General enquiry',
            'message_text' => 'Please create a portal account for me.',
            'source_page' => 'contact',
            'request_account' => '1',
            'declared_age' => '17',
            'self_declared_adult' => '1',
            'g-recaptcha-response' => 'invalid-token',
            'website' => '',
        ]);

        $after = $messagesTable->find()->count();

        $this->assertResponseOk();
        $this->assertSame($before, $after);
        $this->assertResponseContains('Your age and 18+ declaration do not match.');
    }

    public function testCustomerAccessRequestRequiresAdultConfirmationForAdultAge(): void
    {
        Configure::write('Recaptcha.secret_key', '');
        $messagesTable = FactoryLocator::get('Table')->get('Messages');
        $before = $messagesTable->find()->count();

        $this->enableCsrfToken();
        $this->post('/contact', [
            'sender_name' => 'Adult Customer',
            'sender_email' => 'adult@example.com',
            'sender_phone' => '0400000000',
            'subject' => 'General enquiry',
            'message_text' => 'Please create a portal account for me.',
            'source_page' => 'contact',
            'request_account' => '1',
            'declared_age' => '19',
            'g-recaptcha-response' => 'invalid-token',
            'website' => '',
        ]);

        $after = $messagesTable->find()->count();

        $this->assertResponseOk();
        $this->assertSame($before, $after);
        $this->assertResponseContains('Please confirm whether you are 18 or older.');
    }
}
