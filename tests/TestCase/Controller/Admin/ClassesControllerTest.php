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
            'location' => 'Room A',
            'capacity' => 12,
            'class_status' => 'scheduled',
        ]);

        $this->assertResponseCode(302);

        $class = FactoryLocator::get('Table')->get('Classes')->find()
            ->select(['class_code', 'start_datetime', 'end_datetime'])
            ->where(['Classes.course_id' => 1, 'Classes.location' => 'Room A'])
            ->orderBy(['Classes.class_id' => 'DESC'])
            ->firstOrFail();

        $this->assertSame('POT-BEG-001', $class->class_code);
        $this->assertSame('2026-05-03 13:00', $class->start_datetime?->format('Y-m-d H:i'));
        $this->assertSame('2026-05-03 15:00', $class->end_datetime?->format('Y-m-d H:i'));
    }

    public function testEditRecalculatesEndTimeFromExistingCourseDuration(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/classes/edit/1', [
            'class_code' => 'POT-101',
            'course_id' => 1,
            'teacher_id' => 1,
            'start_datetime' => '2026-05-01T14:30',
            'location' => 'Studio A',
            'capacity' => 10,
            'class_status' => 'scheduled',
        ]);

        $this->assertResponseCode(302);

        $class = FactoryLocator::get('Table')->get('Classes')->get(1);
        $this->assertSame('2026-05-01 14:30', $class->start_datetime?->format('Y-m-d H:i'));
        $this->assertSame('2026-05-01 16:30', $class->end_datetime?->format('Y-m-d H:i'));
    }
}
