<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

class CategoriesControllerAccessTest extends AppIntegrationTestCase
{
    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $this->get('/categories/add');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testStudentIsForbidden(): void
    {
        $this->loginAsStudent();

        $this->get('/categories/add');

        $this->assertResponseCode(403);
    }

    public function testTeacherIsForbidden(): void
    {
        $this->loginAsTeacher();

        $this->get('/categories/add');

        $this->assertResponseCode(403);
    }

    public function testAdminCanAccessCrud(): void
    {
        $this->loginAsAdmin();

        $this->get('/categories/add');

        $this->assertResponseOk();
    }
}
