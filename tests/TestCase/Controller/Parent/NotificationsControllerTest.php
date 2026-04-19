<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class NotificationsControllerTest extends AppIntegrationTestCase
{
    public function testParentCanMarkOwnNotificationRead(): void
    {
        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/notifications/mark-read/1');

        $this->assertResponseCode(302);

        $notification = FactoryLocator::get('Table')->get('Notifications')->get(1);
        $this->assertTrue((bool)$notification->is_read);
    }

    public function testParentCanMarkLinkedChildNotificationRead(): void
    {
        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/notifications/mark-read/2');

        $this->assertResponseCode(302);

        $notification = FactoryLocator::get('Table')->get('Notifications')->get(2);
        $this->assertTrue((bool)$notification->is_read);
    }

    public function testParentCannotMarkUnrelatedNotificationRead(): void
    {
        $this->loginAsParent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/parent/notifications/mark-read/3');

        $this->assertResponseCode(404);

        $notification = FactoryLocator::get('Table')->get('Notifications')->get(3);
        $this->assertFalse((bool)$notification->is_read);
    }
}
