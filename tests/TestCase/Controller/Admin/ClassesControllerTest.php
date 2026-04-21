<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class ClassesControllerTest extends AppIntegrationTestCase
{
    public function testAddGeneratesClassCodeAutomatically(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/classes/add', [
            'course_id' => 1,
            'teacher_id' => 2,
            'start_datetime' => '2026-05-03T13:00',
            'end_datetime' => '2026-05-03T15:00',
            'location' => 'Room A',
            'capacity' => 12,
            'class_status' => 'scheduled',
        ]);

        $this->assertResponseCode(302);

        $class = FactoryLocator::get('Table')->get('Classes')->find()
            ->select(['class_code'])
            ->where(['Classes.course_id' => 1, 'Classes.location' => 'Room A'])
            ->orderBy(['Classes.class_id' => 'DESC'])
            ->disableHydration()
            ->firstOrFail();

        $this->assertSame('POT-BEG-001', $class['class_code']);
    }
}
