<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class BookingsControllerTest extends AppIntegrationTestCase
{
    public function testStaleSessionVerificationDoesNotBlockParentBookingFormWhenDatabaseIsApproved(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, true);
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/bookings/add/1/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Book Class');
    }

    public function testLiveTeacherRoleRedirectsParentSessionOutOfParentPortal(): void
    {
        $this->setUserRole(6, 'teacher');
        $this->loginAsParent();

        $this->get('/parent/bookings');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/teacher');
        $this->assertSession('teacher', 'Auth.user_role');
    }

    public function testSuspendedParentAccountIsRedirectedToLogin(): void
    {
        $this->setUserAccountStatus(6, 'suspended');
        $this->loginAsParent();

        $this->get('/parent/bookings');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testParentCanOpenBookingFormForScheduledClass(): void
    {
        $this->loginAsParent();

        $this->get('/parent/bookings/add/1/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Book Class');
    }

    public function testParentCannotOpenBookingFormForCancelledClass(): void
    {
        $classes = FactoryLocator::get('Table')->get('Classes');
        $class = $classes->get(1);
        $class->class_status = 'cancelled';
        $classes->saveOrFail($class);

        $this->loginAsParent();

        $this->get('/parent/bookings/add/1/1');

        $this->assertResponseCode(404);
    }

    public function testUnderageParentCannotOpenBookingForm(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, false);
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/bookings/add/1/1');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }

    public function testUnderageParentCannotCancelBooking(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, false);
        $this->loginAsParent(ageVerifiedByAdmin: false);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/bookings/cancel/1');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);
        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame('pending', $booking->booking_status);
    }
}
