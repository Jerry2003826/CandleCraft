<?php
$this->disableAutoLayout();

$siteName = $this->Cms->text('global', 'branding.site_name', 'CandleCraft Academy');

$heroBgFromCms = $this->Cms->image('home', 'hero.background_image');
$bgUrl = $heroBgFromCms ?? $this->Url->build('/image/background.jpg', ['fullBase' => false]);
$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
$homeUrl = $this->Url->build('/');
$contactUrl = $this->Url->build([
    'controller' => 'Pages',
    'action' => 'contact',
    '?' => ['from' => 'homepage'],
]);
$coursesUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index']);
$faviconCms = $this->Cms->image('global', 'branding.favicon_image');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($siteName) ?></title>
    <link rel="icon" type="image/png" href="<?= h($faviconCms ?? $this->Url->build('/favicon.ico')) ?>">
        <?= $this->Html->css('fonts') ?>
        <?= $this->Html->css('home', ['timestamp' => 'force']) ?>

    <style>
         body.site-home {
        height: 100vh;
        overflow: hidden;
    }
    .home-shell {
        height: 100vh;
        overflow: hidden;
    }
    
    .hero-stage {
        background:
            linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)),
            url('<?= h($bgUrl) ?>') center center / cover no-repeat;
        background-size: cover;
    }
</style>
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
                'aboutUrl' => '#main-content',
                'showAboutLink' => false,
                'contactLabel' => $this->Cms->text('global', 'nav.cta_label', 'Enquire'),
                'menuId' => 'home-courses-menu',
            ]) ?>
        </header>

        <main id="main-content">
            <section class="hero-stage" aria-labelledby="landing-title">
                <div class="hero-stage__grain" aria-hidden="true"></div>
                <div class="hero-stage__halo hero-stage__halo--left" aria-hidden="true"></div>
                <div class="hero-stage__halo hero-stage__halo--right" aria-hidden="true"></div>
                <div class="hero-copy">
                    <p class="hero-copy__eyebrow"><?= h($this->Cms->text('home', 'hero.eyebrow', 'A Sanctuary For The Creative Soul.')) ?></p>
                    <h1 id="landing-title"><?= h($this->Cms->text('home', 'hero.title', $siteName)) ?></h1>
                    <div class="hero-copy__actions">
                        <a href="<?= h($coursesUrl) ?>" class="button button--solid"><?= h($this->Cms->text('home', 'hero.cta_primary_label', 'Browse Courses')) ?></a>
                        <a href="<?= h($contactUrl) ?>" class="button button--outline"><?= h($this->Cms->text('home', 'hero.cta_secondary_label', 'Enquire Today')) ?></a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="home-footer" role="contentinfo">
            <p><?= h($this->Cms->text('global', 'branding.copyright_text', '© ' . date('Y') . ' CandleCraft Academy. All rights reserved.')) ?></p>
        </footer>
    </div>
    <?= $this->Html->script(['site-accessibility', 'public-site']) ?>
</body>
</html>
