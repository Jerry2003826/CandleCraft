<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;

class AppView extends View
{
    /**
     * Initialize.
     */
    public function initialize(): void
    {
        $this->addHelper('Badge');
        $this->addHelper('Cms');
    }
}
