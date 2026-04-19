<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class PaymentsControllerTest extends AppIntegrationTestCase
{
    public function testStaleSessionVerificationDoesNotBlockParentPaymentPortalWhenDatabaseIsApproved(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, true);
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/payments');

        $this->assertResponseOk();
        $this->assertResponseContains('Payments');
    }

    public function testUnderageParentCannotOpenPaymentPortal(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, false);
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/payments');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }
}
