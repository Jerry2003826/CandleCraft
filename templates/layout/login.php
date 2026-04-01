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
        <header class="hero-home hero-home--compact">
            <nav class="hero-nav" aria-label="Primary">
                <a href="<?= h($homeUrl) ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Tutoring</span>
                </a>
                <div class="hero-nav__links">
                    <a href="<?= h($homeUrl) ?>">Home</a>
                    <div class="nav-dropdown">
                        <a href="<?= h($coursesUrl) ?>">Courses</a>
                        <div class="nav-dropdown__menu">
                            <div class="nav-dropdown__menu-inner">
                                <a href="<?= h($coursesUrl) ?>?type=pottery">Pottery</a>
                                <a href="<?= h($coursesUrl) ?>?type=knitting">Knitting</a>
                            </div>
                        </div>
                    </div>
                    <a href="<?= h($contactUrl) ?>">Enquire</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Login</a>
                </div>
            </nav>
        </header>

        <main style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>
        </main>

        <footer class="home-footer">
            <p>&copy; <?= date('Y') ?> - CandleCraft Academy. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>