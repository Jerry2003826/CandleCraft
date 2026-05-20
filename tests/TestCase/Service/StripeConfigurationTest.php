<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StripeConfiguration;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class StripeConfigurationTest extends TestCase
{
    private ?string $originalAppEnv = null;
    private ?string $originalCakephpEnv = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAppEnv = getenv('APP_ENV') !== false ? (string)getenv('APP_ENV') : null;
        $this->originalCakephpEnv = getenv('CAKEPHP_ENV') !== false ? (string)getenv('CAKEPHP_ENV') : null;
        putenv('APP_ENV');
        putenv('CAKEPHP_ENV');
    }

    protected function tearDown(): void
    {
        Configure::delete('Stripe.environment');
        Configure::delete('Stripe.secret_key');
        Configure::delete('Stripe.webhook_secret');

        if ($this->originalAppEnv !== null) {
            putenv('APP_ENV=' . $this->originalAppEnv);
        } else {
            putenv('APP_ENV');
        }

        if ($this->originalCakephpEnv !== null) {
            putenv('CAKEPHP_ENV=' . $this->originalCakephpEnv);
        } else {
            putenv('CAKEPHP_ENV');
        }

        parent::tearDown();
    }

    public function testTestModeAcceptsStandardAndRestrictedTestKeys(): void
    {
        Configure::write('Stripe.environment', 'test');
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_test_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_test_123'));
    }

    public function testLiveModeAcceptsStandardAndRestrictedLiveKeys(): void
    {
        Configure::write('Stripe.environment', 'live');
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_live_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_live_123'));
    }

    public function testSecretKeyValidationRejectsInvalidPlaceholders(): void
    {
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey(''));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('   '));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('pk_test_123'));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_not_real'));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('rk_not_real'));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_test_placeholder'));
    }

    public function testWebhookSecretValidationUsesExpectedPrefix(): void
    {
        $this->assertTrue(StripeConfiguration::hasUsableWebhookSecret('whsec_123'));
        $this->assertFalse(StripeConfiguration::hasUsableWebhookSecret('sk_test_123'));
        $this->assertFalse(StripeConfiguration::hasUsableWebhookSecret('whsec_placeholder'));
    }

    public function testStripeTestModeCanUseHostedCheckoutWithoutWebhookSecret(): void
    {
        Configure::write('Stripe.environment', 'test');
        Configure::write('Stripe.secret_key', 'sk_test_123');
        Configure::write('Stripe.webhook_secret', null);

        $this->assertFalse(StripeConfiguration::isCheckoutReady());
        $this->assertTrue(StripeConfiguration::isHostedCheckoutReady());
    }

    public function testLiveHostedCheckoutStillRequiresWebhookSecret(): void
    {
        Configure::write('Stripe.environment', 'live');
        Configure::write('Stripe.secret_key', 'sk_live_123');
        Configure::write('Stripe.webhook_secret', null);

        $this->assertFalse(StripeConfiguration::isCheckoutReady());
        $this->assertFalse(StripeConfiguration::isHostedCheckoutReady());

        Configure::write('Stripe.webhook_secret', 'whsec_123');
        $this->assertTrue(StripeConfiguration::isHostedCheckoutReady());
    }

    public function testProductionReadinessRejectsTestKeys(): void
    {
        $previousDebug = Configure::read('debug');
        Configure::write('debug', false);
        Configure::write('Stripe.environment', 'live');

        try {
            $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_test_123'));
            $this->assertFalse(StripeConfiguration::hasUsableSecretKey('rk_test_123'));
            $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_live_123'));
            $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_live_123'));
        } finally {
            Configure::write('debug', $previousDebug);
        }
    }

    public function testStagingEnvironmentCanExplicitlyAllowTestKeys(): void
    {
        $previousDebug = Configure::read('debug');
        Configure::write('debug', false);
        Configure::write('Stripe.environment', 'test');
        putenv('APP_ENV=staging');

        try {
            $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_test_123'));
            $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_test_123'));
        } finally {
            Configure::write('debug', $previousDebug);
            putenv('APP_ENV');
        }
    }

    public function testTestEnvironmentRejectsLiveKeys(): void
    {
        Configure::write('Stripe.environment', 'test');

        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_live_123'));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('rk_live_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_test_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_test_123'));
    }

    public function testUnknownConfiguredEnvironmentFailsClosed(): void
    {
        Configure::write('Stripe.environment', 'qa');

        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_test_123'));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_live_123'));
        $this->assertFalse(StripeConfiguration::isCheckoutReady());
    }
}
