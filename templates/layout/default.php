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
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <header class="hero-home hero-home--compact">
            <?php
            $identity = $this->request->getAttribute('identity');
            $portalUrl = null;
            if ($identity) {
                $role = $identity->get('user_role');
                $portalPrefix = match ($role) {
                    'admin' => 'Admin',
                    'teacher' => 'Teacher',
                    'student' => 'Consumer',
                    default => null,
                };
                if ($portalPrefix) {
                    $portalUrl = $this->Url->build(['prefix' => $portalPrefix, 'controller' => 'Dashboard', 'action' => 'index']);
                }
            }
            echo $this->element('public_nav', [
                'homeUrl' => $this->Url->build('/'),
                'coursesUrl' => $this->Url->build(['controller' => 'Courses', 'action' => 'index']),
                'contactUrl' => $this->Url->build(['controller' => 'Pages', 'action' => 'contact']),
                'loginUrl' => $this->Url->build(['controller' => 'Users', 'action' => 'login']),
                'portalUrl' => $portalUrl,
                'showHomeLink' => true,
                'contactLabel' => 'Enquire',
                'menuId' => 'default-courses-menu',
            ]);
            ?>
        </header>

        <main id="main-content" style="max-width: 1400px; margin: auto; padding: 0;">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </main>

        <footer class="home-footer">
            <p>&copy; <?= date('Y') ?> - CandleCraft Academy. All rights reserved.</p>
        </footer>
    </div>
    <?= $this->Html->script(['site-accessibility', 'public-site']) ?>
</body>
</html>
