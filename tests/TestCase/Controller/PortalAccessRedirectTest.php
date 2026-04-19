<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

class PortalAccessRedirectTest extends AppIntegrationTestCase
{
    public function testTeacherAccessingAdminAreaIsRedirectedWithoutLogout(): void
    {
        $this->loginAsTeacher();

        $this->get('/admin/dashboard');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/teacher');
        $this->assertRedirectNotContains('/login');
        $this->assertSession(2, 'Auth.user_id');
        $this->assertSession('teacher', 'Auth.user_role');
    }

    public function testAdminAccessingTeacherAreaIsRedirectedWithoutLogout(): void
    {
        $this->loginAsAdmin();

        $this->get('/teacher/resources');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
        $this->assertRedirectNotContains('/login');
        $this->assertSession(1, 'Auth.user_id');
        $this->assertSession('admin', 'Auth.user_role');
    }

    public function testTeacherAccessingConsumerAreaIsRedirectedWithoutLogout(): void
    {
        $this->loginAsTeacher();

        $this->get('/consumer/bookings');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/teacher');
        $this->assertRedirectNotContains('/login');
        $this->assertSession(2, 'Auth.user_id');
        $this->assertSession('teacher', 'Auth.user_role');
    }

    public function testParentAccessingAdminAreaIsRedirectedWithoutLogout(): void
    {
        $this->loginAsParent();

        $this->get('/admin/dashboard');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');
        $this->assertRedirectNotContains('/login');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }

    public function testParentDashboardLoadsWithoutConsumerRedirectLoop(): void
    {
        $this->loginAsParent();

        $this->get('/parent/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('Parent Dashboard');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }
}
