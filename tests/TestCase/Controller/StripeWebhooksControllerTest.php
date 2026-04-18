<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class StripeWebhooksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testInvalidSignatureReturnsBadRequestWithoutLoginRedirect(): void
    {
        Configure::write('Stripe.webhook_secret', 'whsec_test');

        $this->post('/stripe/webhook', '{}');

        $this->assertResponseCode(400);
        $this->assertResponseNotContains('login');
    }

    public function testLegacyConsumerWebhookReturnsGone(): void
    {
        $this->post('/consumer/payments/webhook', '{}');

        $this->assertResponseCode(410);
        $this->assertResponseContains('Stripe webhook endpoint has moved');
    }

    public function testLegacyStudentWebhookReturnsGone(): void
    {
        $this->post('/student/payments/webhook', '{}');

        $this->assertResponseCode(410);
        $this->assertResponseContains('Stripe webhook endpoint has moved');
    }
}
