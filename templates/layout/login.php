<?php
/**
 * @var \App\View\AppView $this
 */
$homeUrl = $this->Url->build('/');
$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
$contactUrl = $this->Url->build(['controller' => 'Pages', 'action' => 'contact']);
$coursesUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy - Portal Login</title>
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
            <?= $this->element('public_nav', [
                'homeUrl' => $homeUrl,
                'coursesUrl' => $coursesUrl,
                'contactUrl' => $contactUrl,
                'loginUrl' => $loginUrl,
                'showHomeLink' => true,
                'contactLabel' => 'Enquire',
                'menuId' => 'login-courses-menu',
            ]) ?>
        </header>

        <main id="main-content" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
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
