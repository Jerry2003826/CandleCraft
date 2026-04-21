<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Consumer;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class AccountControllerTest extends AppIntegrationTestCase
{
    public function testIndexShowsCustomerAccountDetails(): void
    {
        $this->loginAsStudent();

        $this->get('/consumer/account');

        $this->assertResponseOk();
        $this->assertResponseContains('My Account');
        $this->assertResponseContains('student-one@candlecraft.com');
        $this->assertResponseContains('Student One');
    }

    public function testUnverifiedStudentCanStillOpenAccountEdit(): void
    {
        $this->setUserAgeVerifiedByAdmin(4, false);
        $this->loginAsStudent(ageVerifiedByAdmin: false);

        $this->get('/consumer/account/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('Edit My Account');
    }

    public function testEditUpdatesDetailsAndClearsAdultVerificationForMinor(): void
    {
        $this->setUserAgeVerifiedByAdmin(4, true);
        $this->loginAsStudent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/consumer/account/edit', [
            'email' => 'student-one-updated@candlecraft.com',
            'student_name' => 'Student One Updated',
            'declared_age' => 17,
            'date_of_birth' => '2010-01-01',
            'medical_notes' => 'Needs lower wheel height.',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/consumer/account');

        $students = FactoryLocator::get('Table')->get('Students');
        $users = FactoryLocator::get('Table')->get('Users');

        $student = $students->get(1);
        $user = $users->get(4);

        $this->assertSame('Student One Updated', $student->student_name);
        $this->assertSame('2010-01-01', $student->date_of_birth?->format('Y-m-d'));
        $this->assertSame('Needs lower wheel height.', $student->medical_notes);
        $this->assertSame('student-one-updated@candlecraft.com', $user->email);
        $this->assertFalse((bool)$user->age_verified_by_admin);
    }
}
