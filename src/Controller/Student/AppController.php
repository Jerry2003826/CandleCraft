<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Controller\AppController as BaseAppController;
use Cake\Event\EventInterface;

class AppController extends BaseAppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Legacy Student prefix: redirect to Consumer portal
        $event->stopPropagation();
        $this->setResponse($this->redirect(['prefix' => 'Consumer', 'controller' => 'Dashboard', 'action' => 'index']));
    }
}
