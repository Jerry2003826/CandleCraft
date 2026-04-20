<?php
$this->disableAutoLayout();

$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
$homeUrl = $this->Url->build('/');
$contactUrl = $this->Url->build([
    'controller' => 'Pages',
    'action' => 'contact',
    '?' => ['from' => 'homepage'],
]);
$coursesUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['fonts', 'home']) ?>
</head>
<body class="site-home">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="home-shell">
        <header class="hero-home">
            <?= $this->element('public_nav', [
                'homeUrl' => $homeUrl,
                'coursesUrl' => $coursesUrl,
                'contactUrl' => $contactUrl,
                'loginUrl' => $loginUrl,
                'showHomeLink' => false,
                'contactLabel' => 'Enquiry Form',
                'menuId' => 'home-courses-menu',
            ]) ?>
        </header>

        <main id="main-content">
            <section class="hero-stage" aria-labelledby="landing-title">
                <div class="hero-stage__grain" aria-hidden="true"></div>
                <div class="hero-stage__halo hero-stage__halo--left" aria-hidden="true"></div>
                <div class="hero-stage__halo hero-stage__halo--right" aria-hidden="true"></div>
                <div class="hero-copy">
                    <p class="hero-copy__eyebrow">A Sanctuary For The Creative Soul.</p>
                    <h1 id="landing-title">CandleCraft Academy</h1>
                    <div class="hero-copy__actions">
                        <a href="<?= h($coursesUrl) ?>" class="button button--solid">Browse Courses</a>
                        <a href="<?= h($contactUrl) ?>" class="button button--outline">Open Enquiry Form</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="home-footer" role="contentinfo">
            <p>&copy; <?= date('Y') ?> CandleCraft Academy. All rights reserved.</p>
        </footer>
    </div>
    <script src="/js/site-accessibility.js"></script>
    <script src="/js/public-site.js"></script>
</body>
</html>
