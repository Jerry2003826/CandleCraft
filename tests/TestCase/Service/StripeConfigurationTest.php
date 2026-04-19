<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StripeConfiguration;
use Cake\TestSuite\TestCase;

class StripeConfigurationTest extends TestCase
{
    public function testSecretKeyValidationSupportsStandardAndRestrictedKeys(): void
    {
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_test_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('sk_live_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_test_123'));
        $this->assertTrue(StripeConfiguration::hasUsableSecretKey('rk_live_123'));
    }

    public function testSecretKeyValidationRejectsInvalidPlaceholders(): void
    {
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey(''));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('   '));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('pk_test_123'));
        $this->assertFalse(StripeConfiguration::hasUsableSecretKey('sk_test_placeholder'));
    }

    public function testWebhookSecretValidationUsesExpectedPrefix(): void
    {
        $this->assertTrue(StripeConfiguration::hasUsableWebhookSecret('whsec_123'));
        $this->assertFalse(StripeConfiguration::hasUsableWebhookSecret('sk_test_123'));
        $this->assertFalse(StripeConfiguration::hasUsableWebhookSecret('whsec_placeholder'));
    }
}
