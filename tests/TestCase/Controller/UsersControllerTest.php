<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;

class UsersControllerTest extends AppIntegrationTestCase
{
    public function testForgotPasswordPageRenders(): void
    {
        $this->get('/users/forgot-password');

        $this->assertResponseOk();
        $this->assertResponseContains('Forgot Password');
    }

    public function testInvalidResetTokenRedirectsWithoutDatabaseError(): void
    {
        $this->get('/users/reset-password/not-a-real-token');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testResetPasswordConsumesTokenAndRedirectsToLogin(): void
    {
        $users = FactoryLocator::get('Table')->get('Users');
        $user = $users->get(4);
        $user->reset_token = 'single-use-token';
        $user->reset_token_expires = new DateTime('+1 hour');
        $users->saveOrFail($user);

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/reset-password/single-use-token', [
            'password' => 'new-strong-password',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');

        $updated = $users->get(4);
        $this->assertNull($updated->reset_token);
        $this->assertNull($updated->reset_token_expires);
        $this->assertTrue((new DefaultPasswordHasher())->check('new-strong-password', $updated->password_hash));

        $this->get('/users/reset-password/single-use-token');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    public function testAdminLoginSetsTopRightToast(): void
    {
        $this->setFixturePassword(1, 'admin-password');

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/login', [
            'email' => 'admin@candlecraft.com',
            'password' => 'admin-password',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/admin');
        $this->assertSession('Admin admin signed in successfully.', 'Flash.login_toast.0.message');
    }

    public function testCustomerLoginSetsTopRightToast(): void
    {
        $this->createCustomerUser('customer-one@candlecraft.com', 'customer1', 'customer-password');

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/login', [
            'email' => 'customer-one@candlecraft.com',
            'password' => 'customer-password',
        ]);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/consumer');
        $this->assertSession('Customer customer1 signed in successfully.', 'Flash.login_toast.0.message');
    }

    private function setFixturePassword(int $userId, string $password): void
    {
        $users = FactoryLocator::get('Table')->get('Users');
        $user = $users->get($userId);
        $user->password_hash = $password;
        $users->saveOrFail($user);
    }

    private function createCustomerUser(string $email, string $username, string $password): void
    {
        $users = FactoryLocator::get('Table')->get('Users');
        $user = $users->newEntity(
            [
                'username' => $username,
                'email' => $email,
                'password_hash' => $password,
                'user_role' => 'customer',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'created_at' => '2026-04-28 10:00:00',
                'updated_at' => '2026-04-28 10:00:00',
            ],
            [
                'accessibleFields' => [
                    'user_role' => true,
                    'account_status' => true,
                    'age_verified_by_admin' => true,
                    'self_declared_adult' => true,
                ],
            ],
        );
        $users->saveOrFail($user);
    }
}
