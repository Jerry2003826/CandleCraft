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

    protected function rejectUnauthenticatedAccess(string $message): Response
    {
        $this->Flash->error(__($message));

        return $this->redirect(['plugin' => false, 'prefix' => false, 'controller' => 'Users', 'action' => 'login']);
    }

    protected function redirectAuthenticatedRoleMismatch(mixed $identity, string $message): Response
    {
        $role = $this->identityRole($identity);
        if ($role === '') {
            return $this->rejectUnauthenticatedAccess($message);
        }

        $this->Flash->error(__($message));

        return $this->redirect($this->dashboardRouteForRole($role));
    }

    protected function syncAuthenticatedUserState(mixed $user): void
    {
        if (!is_object($user) || !method_exists($user, 'get')) {
            return;
        }

        $session = $this->request->getSession();
        $auth = (array)$session->read('Auth');

        foreach ([
            'user_id',
            'email',
            'username',
            'user_role',
            'account_status',
            'age_verified_by_admin',
            'self_declared_adult',
        ] as $field) {
            $value = $user->get($field);
            if ($value !== null) {
                $auth[$field] = $value;
            }
        }

        $session->write('Auth', $auth);
    }

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

    protected function shortCircuitRequest(EventInterface $event, Response $response): void
    {
        $event->stopPropagation();
        $event->setResult($response);
        $this->setResponse($response);
    }
}
