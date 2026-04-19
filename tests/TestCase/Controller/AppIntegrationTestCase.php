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
        'app.TeacherAvailabilities',
        'app.Messages',
        'app.Categories',
        'app.PaymentWebhookIncidents',
    ];

    protected function loginAsAdmin(): void
    {
        $this->session(['Auth' => $this->identity(1, 'admin@candlecraft.com', 'admin', 'admin')]);
    }

    protected function loginAsTeacher(int $userId = 2): void
    {
        $email = $userId === 3 ? 'teacher-two@candlecraft.com' : 'teacher-one@candlecraft.com';
        $username = $userId === 3 ? 'teacher2' : 'teacher1';
        $this->session(['Auth' => $this->identity($userId, $email, 'teacher', $username)]);
    }

    protected function loginAsStudent(int $userId = 4): void
    {
        $email = $userId === 5 ? 'student-two@candlecraft.com' : 'student-one@candlecraft.com';
        $username = $userId === 5 ? 'student2' : 'student1';
        $this->session(['Auth' => $this->identity($userId, $email, 'student', $username)]);
    }

    protected function loginAsParent(int $userId = 6): void
    {
        $this->session(['Auth' => $this->identity($userId, 'parent-one@candlecraft.com', 'parent', 'parent1')]);
    }

    private function identity(int $userId, string $email, string $role, string $username): array
    {
        return [
            'user_id' => $userId,
            'email' => $email,
            'username' => $username,
            'user_role' => $role,
            'account_status' => 'active',
        ];
    }
}
