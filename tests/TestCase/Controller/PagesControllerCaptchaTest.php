<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class PagesControllerCaptchaTest extends AppIntegrationTestCase
{
    public function testMissingRecaptchaConfigDoesNotSaveMessage(): void
    {
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

        $this->assertResponseOk();
        $this->assertSame($before, $after);
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
}
