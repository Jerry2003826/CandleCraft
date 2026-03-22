<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy - Login</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <div class="login-page">
        <nav class="login-nav">
            <div class="brand">CandleCraft Academy</div>
            <div class="nav-links">
                <a href="#">Courses</a>
                <a href="#">Home</a>
                <a href="#">Gallery</a>
                <a href="#">Contact Us</a>
                <a href="#">FAQ</a>
                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="btn-login">Log In</a>
            </div>
        </nav>

        <div class="login-hero">
            <div class="login-card">
                <div class="logo">
                    <div class="icon">&#x1F3A8;</div>
                    <h2>CandleCraft Academy</h2>
                </div>
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </div>
        </div>

        <footer class="login-footer">
            <p>&copy; <?= date('Y') ?> - CandleCraft Academy. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
