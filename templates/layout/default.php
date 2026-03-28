<?php
/**
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
    <?= $this->Html->css(['fonts', 'cake', 'admin', 'home']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body class="site-home">
    <div class="home-shell">
        <header class="hero-home hero-home--compact">
            <nav class="hero-nav" aria-label="Primary">
                <a href="<?= $this->Url->build('/') ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Tutoring</span>
                </a>
                <div class="hero-nav__links">
                    <a href="<?= $this->Url->build('/') ?>">Home</a>
                    <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>">Courses</a>
                    <a href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'contact']) ?>">Enquire</a>
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
                            <a href="<?= $this->Url->build(['prefix' => $portalPrefix, 'controller' => 'Dashboard', 'action' => 'index']) ?>" class="hero-nav__login">My Portal</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="hero-nav__login">Log In</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <main style="max-width: 1400px; margin: auto; padding: 0;">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </main>

        <footer class="home-footer">
            <p>&copy; <?= date('Y') ?> - CandleCraft Academy. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>