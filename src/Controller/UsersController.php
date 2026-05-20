<?php
declare(strict_types=1);

namespace App\Controller;

use App\Mailer\PasswordResetMailer;
use Cake\Event\EventInterface;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\Transport\MailTransport;
use Cake\Mailer\Transport\SmtpTransport;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Throwable;
use function Cake\Core\env;

class UsersController extends AppController
{
    /**
     * Before filter.
     *
     * @param mixed $event Event.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'forgotPassword', 'resetPassword']);
    }

    /**
     * Login.
     */
    public function login(): ?Response
    {
        $this->viewBuilder()->setLayout('login');
        $this->response = $this->response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');

        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $user = $this->Authentication->getIdentity();
            $role = $user->get('user_role');
            $this->setLoginToast($user);

            if ($role === 'admin') {
                return $this->redirect(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'teacher') {
                return $this->redirect(['prefix' => 'Teacher', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'student') {
                return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'customer') {
                return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($role === 'parent') {
                return $this->redirect(['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'index']);
            }

            $this->Authentication->logout();
            $this->Flash->info(__(
                'This portal currently supports admin, teacher, student, parent, and customer logins.',
            ));

            return $this->redirect('/');
        }

        if ($this->request->is('post') && $result && !$result->isValid()) {
            $this->Flash->error(__(
                'Invalid email or password.',
            ));
        }

        return null;
    }

    /**
     * Set login toast.
     *
     * @param mixed $user User.
     */
    private function setLoginToast(mixed $user): void
    {
        $username = is_object($user) && method_exists($user, 'get')
            ? (string)($user->get('username') ?: $user->get('email') ?: 'user')
            : 'user';
        $role = is_object($user) && method_exists($user, 'get')
            ? (string)$user->get('user_role')
            : '';
        $roleLabel = match ($role) {
            'admin' => 'Admin',
            'teacher' => 'Teacher',
            'student' => 'Student',
            'parent' => 'Parent',
            'customer' => 'Customer',
            default => 'User',
        };

        $this->Flash->success(__('{0} {1} signed in successfully.', $roleLabel, $username), [
            'key' => 'login_toast',
        ]);
    }

    /**
     * Logout.
     */
    public function logout(): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->Authentication->logout();
        $session = $this->request->getSession();
        $session->delete('Auth');
        $session->renew();

        $response = $this->redirect(['action' => 'login'])
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');

        $csrfCookie = new Cookie('csrfToken', '', null, '/');

        return $response->withExpiredCookie($csrfCookie);
    }

    /**
     * Forgot password.
     */
    public function forgotPassword(): ?Response
    {
        $this->viewBuilder()->setLayout('login');
        if ($this->request->is('post')) {
            $email = trim((string)$this->request->getData('email'));
            $user = $this->Users->findByEmail($email)->first();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->reset_token = $token;
                $user->reset_token_expires = new DateTime('+1 hour');

                if (!$this->Users->save($user)) {
                    Log::error('Password reset token could not be saved.', [
                        'email' => $email,
                        'errors' => $user->getErrors(),
                    ]);
                    $this->Flash->error(__(
                        'Password reset is temporarily unavailable. Please contact an administrator.',
                    ));

                    return $this->redirect(['action' => 'login']);
                }

                $resetUrl = Router::url([
                    'controller' => 'Users',
                    'action' => 'resetPassword',
                    $token,
                ], true);

                if (!$this->hasUsableEmailConfiguration()) {
                    Log::error('Password reset email is not configured.', [
                        'email' => $email,
                    ]);
                    $this->clearPasswordResetToken($user);
                    $this->Flash->error(__(
                        'Password reset email is not configured right now. Please contact an administrator.',
                    ));

                    return $this->redirect(['action' => 'login']);
                }

                try {
                    (new PasswordResetMailer('default'))
                        ->resetLink([
                            'recipient_email' => $email,
                            'recipient_name' => (string)($user->username ?: 'there'),
                            'reset_url' => $resetUrl,
                            'expires_in' => '1 hour',
                        ])
                        ->deliver();
                } catch (Throwable $exception) {
                    Log::error('Password reset email could not be sent.', [
                        'email' => $email,
                        'message' => $exception->getMessage(),
                    ]);
                    $this->clearPasswordResetToken($user);
                    $this->Flash->error(__(
                        'Password reset email could not be sent right now. Please contact an administrator.',
                    ));

                    return $this->redirect(['action' => 'login']);
                }
            }

            $this->Flash->success(__(
                'If that email exists, a reset link has been sent.',
            ));

            return $this->redirect(['action' => 'login']);
        }

        return null;
    }

    /**
     * Reset password.
     *
     * @param string|null $token Token.
     */
    public function resetPassword(?string $token = null): ?Response
    {
        $this->viewBuilder()->setLayout('login');
        if (!$token) {
            return $this->redirect(['action' => 'login']);
        }

        $user = $this->Users->findByResetToken($token)
            ->where(['reset_token_expires >=' => new DateTime()])
            ->first();

        if (!$user) {
            $this->Flash->error(__(
                'Invalid or expired reset link.',
            ));

            return $this->redirect(['action' => 'login']);
        }

        if ($this->request->is('post')) {
            $password = (string)$this->request->getData('password');
            if (strlen($password) < 8) {
                $this->Flash->error(__(
                    'Password could not be updated. Please use at least 8 characters and try again.',
                ));
                $this->set(compact('user', 'token'));

                return null;
            }

            $user->password_hash = $password;
            $user->reset_token = null;
            $user->reset_token_expires = null;

            if ($this->Users->save($user)) {
                $this->Flash->success(__(
                    'Password updated. Please log in.',
                ));

                return $this->redirect(['action' => 'login']);
            }

            $this->Flash->error(__(
                'Password could not be updated. Please use at least 8 characters and try again.',
            ));
        }

        $this->set(compact('user', 'token'));

        return null;
    }

    /**
     * Clear password reset token.
     *
     * @param mixed $user User.
     */
    private function clearPasswordResetToken(mixed $user): void
    {
        $user->reset_token = null;
        $user->reset_token_expires = null;

        if (!$this->Users->save($user)) {
            Log::warning('Password reset token could not be cleared after email delivery failure.', [
                'user_id' => $user->user_id ?? null,
                'errors' => method_exists($user, 'getErrors') ? $user->getErrors() : [],
            ]);
        }
    }

    /**
     * Has usable email configuration.
     */
    private function hasUsableEmailConfiguration(): bool
    {
        $transport = (array)TransportFactory::getConfig('default');
        $profile = (array)Mailer::getConfig('default');
        $from = (array)($profile['from'] ?? []);
        $fromAddress = trim((string)(array_key_first($from) ?? env('EMAIL_FROM_ADDRESS', env('EMAIL_SMTP_USERNAME', ''))));

        if ($fromAddress === '' || str_ends_with(strtolower($fromAddress), '@localhost')) {
            return false;
        }

        $className = (string)($transport['className'] ?? '');
        $host = trim((string)($transport['host'] ?? ''));
        $username = trim((string)($transport['username'] ?? ''));
        $password = (string)($transport['password'] ?? '');
        $url = trim((string)($transport['url'] ?? ''));

        if ($className === SmtpTransport::class) {
            return $url !== '' || ($host !== '' && $username !== '' && $password !== '');
        }

        if ($className === MailTransport::class) {
            return true;
        }

        return $className !== '';
    }
}
