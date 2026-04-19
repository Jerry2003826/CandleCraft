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

    public function testParentCanCreateBookingForOngoingClassViaSharedBookingService(): void
    {
        $classes = FactoryLocator::get('Table')->get('Classes');
        $class = $classes->get(2);
        $class->class_status = 'ongoing';
        $classes->saveOrFail($class);

        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/bookings/add/2/1', [
            'student_id' => 1,
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent/payments/process/');

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->where([
                'Bookings.class_id' => 2,
                'Bookings.student_id' => 1,
                'Bookings.parent_id' => 1,
            ])
            ->firstOrFail();

        $this->assertSame('pending', $booking->booking_status);
    }

    public function testParentCanReactivateCancelledBookingAndClaimParentOwnership(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'cancelled';
        $booking->parent_id = null;
        $bookings->saveOrFail($booking);

        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/bookings/add/1/1', [
            'student_id' => 1,
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent/payments/process/1');

        $booking = $bookings->get(1);
        $this->assertSame('pending', $booking->booking_status);
        $this->assertSame(1, $booking->parent_id);
    }

    public function testParentCannotCreateBookingForCompletedClassViaDirectPost(): void
    {
        $classes = FactoryLocator::get('Table')->get('Classes');
        $class = $classes->get(2);
        $class->class_status = 'completed';
        $classes->saveOrFail($class);

        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/bookings/add/2/1', [
            'student_id' => 1,
        ]);

        $this->assertResponseCode(404);
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

    public function testParentDuplicateBookingMessageWinsOverFullClassOnPost(): void
    {
        $classes = FactoryLocator::get('Table')->get('Classes');
        $class = $classes->get(1);
        $class->capacity = 1;
        $classes->saveOrFail($class);

        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/bookings/add/1/1', [
            'student_id' => 1,
        ]);

        $this->assertResponseCode(200);
        $this->assertResponseContains('This student is already booked for this class.');
    }
}
