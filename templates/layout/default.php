<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */

$appTitle = 'CandleCraft Academy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $appTitle ?>
        <?php if ($this->fetch('title')): ?> - <?= $this->fetch('title') ?><?php endif; ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake', 'admin']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <nav class="login-nav">
        <div class="brand"><?= $appTitle ?></div>
        <div class="nav-links">
            <a href="<?= $this->Url->build('/') ?>">Home</a>
            <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>">Courses</a>
            <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'contact']) ?>">Contact</a>
            <?php
            $identity = $this->request->getAttribute('identity');
            if ($identity):
                $role = $identity->get('user_role');
                $portalPrefix = match ($role) {
                    'admin' => 'Admin',
                    'teacher' => 'Teacher',
                    'student' => 'Student',
                    'parent' => 'Parent',
                    default => null,
                };
            ?>
                <?php if ($portalPrefix): ?>
                    <a href="<?= $this->Url->build(['prefix' => $portalPrefix, 'controller' => 'Dashboard', 'action' => 'index']) ?>" class="btn-login">My Portal</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="btn-login">Log In</a>
            <?php endif; ?>
        </div>
    </nav>
    <main style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
    <footer class="login-footer">
        <p>&copy; <?= date('Y') ?> - CandleCraft Academy. All rights reserved.</p>
    </footer>
</body>
</html>
