<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class PaymentsControllerTest extends AppIntegrationTestCase
{
    public function testUnderageParentCannotOpenPaymentPortal(): void
    {
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/payments');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }
}
