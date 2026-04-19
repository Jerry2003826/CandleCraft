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

    public function testLiveTeacherRoleRedirectsAdminSessionOutOfAdminPortal(): void
    {
        $this->setUserRole(1, 'teacher');
        $this->loginAsAdmin();

        $this->get('/admin/dashboard');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/teacher');
        $this->assertSession('teacher', 'Auth.user_role');
    }

    public function testSuspendedAdminAccountIsRedirectedToLogin(): void
    {
        $this->setUserAccountStatus(1, 'suspended');
        $this->loginAsAdmin();

        $this->get('/admin/dashboard');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testLiveAdminRoleRedirectsTeacherSessionOutOfTeacherPortal(): void
    {
        $this->setUserRole(2, 'admin');
        $this->loginAsTeacher();

        $this->get('/teacher/resources');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
        $this->assertSession('admin', 'Auth.user_role');
    }

    public function testSuspendedTeacherAccountIsRedirectedToLogin(): void
    {
        $this->setUserAccountStatus(2, 'suspended');
        $this->loginAsTeacher();

        $this->get('/teacher/resources');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testDeletedAdminAccountIsRedirectedToLogin(): void
    {
        $this->session(['Auth' => [
            'user_id' => 999,
            'email' => 'admin@candlecraft.com',
            'username' => 'admin',
            'user_role' => 'admin',
            'account_status' => 'active',
            'age_verified_by_admin' => true,
            'self_declared_adult' => true,
        ]]);

        $this->get('/admin/dashboard');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testDeletedTeacherAccountIsRedirectedToLogin(): void
    {
        $this->session(['Auth' => [
            'user_id' => 999,
            'email' => 'teacher-one@candlecraft.com',
            'username' => 'teacher1',
            'user_role' => 'teacher',
            'account_status' => 'active',
            'age_verified_by_admin' => true,
            'self_declared_adult' => true,
        ]]);

        $this->get('/teacher/resources');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testAuthenticatedParentOpeningLoginPageIsRedirectedToParentDashboard(): void
    {
        $this->loginAsParent();

        $this->get('/login');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');
        $this->assertRedirectNotContains('/login?');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }
}
