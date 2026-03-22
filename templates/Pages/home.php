<?php
/**
 * @var \App\View\AppView $this
 */
$this->disableAutoLayout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy</title>
    <?= $this->Html->css(['admin']) ?>
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

        <div class="login-hero" style="flex-direction: column; text-align: center;">
            <h1 style="color: #fff; font-size: 48px; margin-bottom: 16px;">Welcome to CandleCraft Academy</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 20px; max-width: 600px; margin-bottom: 32px;">
                Pottery and knitting tutoring for all skill levels. Discover your creative potential today.
            </p>
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>"
               class="btn btn-primary" style="font-size: 18px; padding: 14px 40px;">
                Admin Login
            </a>
        </div>

        <footer class="login-footer">
            <p>&copy; <?= date('Y') ?> - CandleCraft Academy. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
