<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * Reject unauthenticated access.
     *
     * @param mixed $message Message.
     */
    protected function rejectUnauthenticatedAccess(string $message): Response
    {
        $this->Flash->error(__($message));

        return $this->redirect(['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Redirect authenticated role mismatch.
     *
     * @param mixed $identity Identity.
     * @param mixed $message Message.
     */
    protected function redirectAuthenticatedRoleMismatch(mixed $identity, string $message): Response
    {
        $role = $this->identityRole($identity);
        if ($role === '') {
            return $this->rejectUnauthenticatedAccess($message);
        }

        $this->Flash->error(__($message));

        return $this->redirect($this->dashboardRouteForRole($role));
    }

    /**
     * Sync authenticated user state.
     *
     * @param mixed $user User.
     */
    protected function syncAuthenticatedUserState(mixed $user): void
    {
        if (!is_object($user) || !method_exists($user, 'get')) {
            return;
        }

        $session = $this->request->getSession();
        $auth = (array)$session->read('Auth');

        foreach (
            [
            'user_id',
            'email',
            'username',
            'user_role',
            'account_status',
            'age_verified_by_admin',
            'self_declared_adult',
            ] as $field
        ) {
            $value = $user->get($field);
            if ($value !== null) {
                $auth[$field] = $value;
            }
        }

        $session->write('Auth', $auth);
    }

    /**
     * Load active authenticated user.
     *
     * @param mixed $event Event.
     * @param mixed $identity Identity.
     * @param mixed $inactiveMessage Inactivemessage.
     */
    protected function loadActiveAuthenticatedUser(
        EventInterface $event,
        mixed $identity,
        string $inactiveMessage = 'Your account is no longer active. Please contact an administrator.',
    ): ?object {
        if (!is_object($identity) || !method_exists($identity, 'get')) {
            $this->Authentication->logout();
            $this->shortCircuitRequest(
                $event,
                $this->redirect(['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login']),
            );

            return null;
        }

        $currentUser = $this->fetchTable('Users')->find()
            ->where(['Users.user_id' => $identity->get('user_id')])
            ->first();

        if (!$currentUser || (string)$currentUser->get('account_status') !== 'active') {
            $this->Flash->error(__($inactiveMessage));
            $this->Authentication->logout();
            $this->shortCircuitRequest(
                $event,
                $this->redirect(['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login']),
            );

            return null;
        }

        $this->syncAuthenticatedUserState($currentUser);

        return $currentUser;
    }

    /**
     * Identity role.
     *
     * @param mixed $identity Identity.
     */
    private function identityRole(mixed $identity): string
    {
        if (!is_object($identity) || !method_exists($identity, 'get')) {
            return '';
        }

        return (string)$identity->get('user_role');
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardRouteForRole(string $role): array
    {
        return match ($role) {
            'admin' => ['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index'],
            'teacher' => ['prefix' => 'Teacher', 'controller' => 'Dashboard', 'action' => 'index'],
            'parent' => ['prefix' => 'Parent', 'controller' => 'Dashboard', 'action' => 'index'],
            'student' => ['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index'],
            'customer' => ['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index'],
            default => ['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login'],
        };
    }

    /**
     * Short circuit request.
     *
     * @param mixed $event Event.
     * @param mixed $response Response.
     */
    protected function shortCircuitRequest(EventInterface $event, Response $response): void
    {
        $event->stopPropagation();
        $event->setResult($response);
        $this->setResponse($response);
    }
}
