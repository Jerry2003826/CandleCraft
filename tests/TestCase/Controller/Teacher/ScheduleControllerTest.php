<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Teacher;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class ScheduleControllerTest extends AppIntegrationTestCase
{
    public function testPastScheduledClassKeepsScheduledLabel(): void
    {
        $this->loginAsTeacher();

        $this->get('/teacher/schedule');

        $this->assertResponseOk();
        $this->assertResponseContains('POT-101');
        $this->assertResponseContains('Scheduled');
        $this->assertResponseNotContains('>Completed</span>');
    }

    public function testClassViewAllowsAttendanceUpdatesWithoutLeavingScheduleContext(): void
    {
        $this->loginAsTeacher();

        $this->get('/teacher/schedule/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('/teacher/attendance/mark');
        $this->assertResponseContains('attendance-status-options');
        $this->assertResponseContains('name="return_to"');
    }
}
