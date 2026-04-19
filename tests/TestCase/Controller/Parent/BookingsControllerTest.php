<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class BookingsControllerTest extends AppIntegrationTestCase
{
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
}
