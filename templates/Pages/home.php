<?php
$this->disableAutoLayout();

$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
$homeUrl = $this->Url->build('/');
$contactUrl = $this->Url->build([
    'controller' => 'Pages',
    'action' => 'contact',
    '?' => ['from' => 'homepage'],
]);

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
            <nav class="hero-nav" aria-label="Primary">
                <a href="<?= h($homeUrl) ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Tutoring</span>
                </a>
                <div class="hero-nav__links">
                    <a href="#overview">About</a>
                    <a href="<?= h($contactUrl) ?>">Enquire</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Login</a>
                </div>
            </nav>

            <section class="hero-stage" aria-labelledby="landing-title">
                <div class="hero-copy">
                    <p class="hero-copy__eyebrow">A Sanctuary For The Creative Soul.</p>
                    <h1 id="landing-title">CandleCraft Academy</h1>
                    <div class="hero-copy__actions">
                        <a href="<?= h($contactUrl) ?>" class="button button--solid">Enquire Today</a>
                    </div>
                </div>
            </section>
        </header>


