<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

abstract class AppIntegrationTestCase extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Admins',
        'app.Teachers',
        'app.Students',
        'app.Parents',
        'app.ParentStudents',
        'app.Courses',
        'app.Classes',
        'app.Bookings',
        'app.Payments',
        'app.LearningResources',
        'app.Notifications',
        'app.TeacherAvailabilities',
        'app.Messages',
        'app.Categories',
        'app.PaymentWebhookIncidents',
    ];

    protected function loginAsAdmin(): void
    {
        $this->session(['Auth' => $this->identity(1, 'admin@candlecraft.com', 'admin', 'admin')]);
    }

    protected function loginAsTeacher(int $userId = 2, bool $ageVerifiedByAdmin = true): void
    {
        $email = $userId === 3 ? 'teacher-two@candlecraft.com' : 'teacher-one@candlecraft.com';
        $username = $userId === 3 ? 'teacher2' : 'teacher1';
        $this->session(['Auth' => $this->identity($userId, $email, 'teacher', $username, $ageVerifiedByAdmin)]);
    }

    protected function loginAsStudent(int $userId = 4, bool $ageVerifiedByAdmin = true): void
    {
        $email = $userId === 5 ? 'student-two@candlecraft.com' : 'student-one@candlecraft.com';
        $username = $userId === 5 ? 'student2' : 'student1';
        $this->session(['Auth' => $this->identity($userId, $email, 'student', $username, $ageVerifiedByAdmin)]);
    }

    protected function loginAsParent(int $userId = 6, bool $ageVerifiedByAdmin = true): void
    {
        $this->session(['Auth' => $this->identity($userId, 'parent-one@candlecraft.com', 'parent', 'parent1', $ageVerifiedByAdmin)]);
    }

    private function identity(int $userId, string $email, string $role, string $username, bool $ageVerifiedByAdmin = true): array
    {
        return [
            'user_id' => $userId,
            'email' => $email,
            'username' => $username,
            'user_role' => $role,
            'account_status' => 'active',
            'age_verified_by_admin' => $ageVerifiedByAdmin,
            'self_declared_adult' => $ageVerifiedByAdmin,
        ];
    }
}
