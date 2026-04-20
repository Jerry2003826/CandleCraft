<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class StudentsControllerTest extends AppIntegrationTestCase
{
    public function testVerifyAgeRejectsMinorBasedOnRecordedDateOfBirth(): void
    {
        $this->setUserAgeVerifiedByAdmin(4, false);

        $students = FactoryLocator::get('Table')->get('Students');
        $student = $students->get(1);
        $student->declared_age = 21;
        $student->date_of_birth = '2012-01-01';
        $students->saveOrFail($student);

        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/students/verify-age/1');

        $this->assertResponseCode(302);

        $user = FactoryLocator::get('Table')->get('Users')->get(4);
        $this->assertFalse((bool)$user->age_verified_by_admin);
    }

    public function testUnverifyAgeRemovesAdultVerification(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/students/unverify-age/1');

        $this->assertResponseCode(302);

        $user = FactoryLocator::get('Table')->get('Users')->get(4);
        $this->assertFalse((bool)$user->age_verified_by_admin);
    }

    public function testVerifyAgeAllowsAdultCustomer(): void
    {
        $this->setUserAgeVerifiedByAdmin(4, false);

        $students = FactoryLocator::get('Table')->get('Students');
        $student = $students->get(1);
        $student->declared_age = 25;
        $student->date_of_birth = '2000-01-01';
        $students->saveOrFail($student);

        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/students/verify-age/1');

        $this->assertResponseCode(302);

        $user = FactoryLocator::get('Table')->get('Users')->get(4);
        $this->assertTrue((bool)$user->age_verified_by_admin);
    }

    public function testEditClearsAdultVerificationWhenAgeFallsBelow18(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/students/edit/1', [
            'student_name' => 'Student One',
            'declared_age' => 16,
            'student_status' => 'active',
            'date_of_birth' => '',
            'medical_notes' => '',
        ]);

        $this->assertResponseCode(302);

        $user = FactoryLocator::get('Table')->get('Users')->get(4);
        $this->assertFalse((bool)$user->age_verified_by_admin);
    }
}
