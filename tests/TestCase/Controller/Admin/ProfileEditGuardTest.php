<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class ProfileEditGuardTest extends AppIntegrationTestCase
{
    public function testStudentEditDoesNotAllowUserRebinding(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/students/edit/1', [
            'student_name' => 'Updated Student',
            'declared_age' => 23,
            'student_status' => 'inactive',
            'date_of_birth' => '2003-05-01',
            'medical_notes' => 'Updated notes',
            'user_id' => 5,
            'created_at' => '1999-01-01 00:00:00',
        ]);

        $this->assertResponseCode(302);

        $student = FactoryLocator::get('Table')->get('Students')->get(1);

        $this->assertSame(4, (int)$student->user_id);
        $this->assertSame('Updated Student', $student->student_name);
        $this->assertSame('inactive', $student->student_status);
        $this->assertSame('Updated notes', (string)$student->medical_notes);
    }

    public function testTeacherEditDoesNotAllowUserRebinding(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/teachers/edit/1', [
            'teacher_name' => 'Updated Teacher',
            'phone_number' => '0400000999',
            'specialization' => 'pottery',
            'teacher_status' => 'inactive',
            'hire_date' => '2025-02-01',
            'user_id' => 3,
            'updated_at' => '1999-01-01 00:00:00',
        ]);

        $this->assertResponseCode(302);

        $teacher = FactoryLocator::get('Table')->get('Teachers')->get(1);

        $this->assertSame(2, (int)$teacher->user_id);
        $this->assertSame('Updated Teacher', $teacher->teacher_name);
        $this->assertSame('0400000999', $teacher->phone_number);
        $this->assertSame('inactive', $teacher->teacher_status);
    }
}
