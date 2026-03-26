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
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Platform</span>
                </a>
                <div class="hero-nav__links">
                    <a href="#overview">About</a>
                    <a href="<?= h($contactUrl) ?>">Book a Lesson</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Portal Login</a>
                </div>
            </nav>

            <section class="hero-stage" aria-labelledby="landing-title">
                <div class="hero-stage__grain"></div>
                <div class="hero-stage__halo hero-stage__halo--left"></div>
                <div class="hero-stage__halo hero-stage__halo--right"></div>

                <aside class="hero-stage__meta" aria-hidden="true">
                    <span class="hero-stage__meta-line"></span>
                    <span class="hero-stage__meta-copy">Overview</span>
                    <span class="hero-stage__meta-year">2026</span>
                </aside>

                <div class="hero-art" aria-hidden="true">
                    <div class="hero-art__panel hero-art__panel--arch"></div>
                    <div class="hero-art__panel hero-art__panel--slats"></div>
                    <div class="hero-art__rings">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="hero-art__arch-outline"></div>
                    <div class="hero-art__console">
                        <div class="hero-art__console-top"></div>
                        <div class="hero-art__console-body"></div>
                    </div>
                    <div class="hero-art__vessel hero-art__vessel--left"></div>
                    <div class="hero-art__vessel hero-art__vessel--center"></div>
                    <div class="hero-art__vessel hero-art__vessel--right"></div>
                    <div class="hero-art__botanical"></div>
                    <div class="hero-art__floor"></div>
                </div>

                <div class="hero-copy">
                    <div class="hero-copy__rating" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <p class="hero-copy__eyebrow">Pottery and knitting</p>
                    <h1 id="landing-title">CandleCraft Academy</h1>
                    <p class="hero-copy__body">
                        A clutter-free, luxurious path into lesson bookings.
                    </p>
                    <div class="hero-copy__actions">
                        <a href="<?= h($contactUrl) ?>" class="button button--solid">Book a Lesson</a>
                    </div>
                </div>
            </section>
        </header>

        <main id="main-content" tabindex="-1">
            <section id="overview" class="section section--overview" aria-labelledby="overview-title">
                <div class="section-heading">
                    <p class="overline">About</p>
                    <h2 id="overview-title">Clarity for families. Less admin for staff.</h2>
                    <p class="section-heading__text">
                        CandleCraft Academy moves pottery and knitting enquiries into one elegant online journey.
                    </p>
                </div>

                <div class="overview-grid">
                    <article class="overview-card overview-card--story">
                        <p class="overline">The vision</p>
                        <h3>One digital home.</h3>
                        <p>
                            Parents can discover the academy, ask about lessons, and move towards booking without the usual friction.
                        </p>
                        <p>
                            The platform is built to reduce administration, support more teachers, and help the academy grow.
                        </p>
                    </article>

                    <article class="overview-card overview-card--atelier">
                        <div class="atelier-scene" aria-hidden="true">
                            <div class="atelier-scene__backdrop"></div>
                            <div class="atelier-scene__spotlight"></div>
                            <div class="atelier-scene__figure"></div>
                            <div class="atelier-scene__wrap"></div>
                            <div class="atelier-scene__animal atelier-scene__animal--left"></div>
                            <div class="atelier-scene__animal atelier-scene__animal--right"></div>
                            <div class="atelier-scene__ledge"></div>
                        </div>
                        <div class="atelier-caption">
                            <span>Atmosphere</span>
                            <strong>Warm, calm, and studio-led.</strong>
                        </div>
                    </article>
                </div>
            </section>

            <section id="ask" class="section section--contact" aria-labelledby="ask-title">
                <div class="contact-shell">
                    <div class="contact-copy">
                        <p class="overline">Book a lesson</p>
                        <h2 id="ask-title">Ask about lessons.</h2>
                        <p class="contact-copy__body">
                            Open the secure enquiry page and our team will reply.
                        </p>
                    </div>

                    <div class="enquiry-card enquiry-card--cta">
                        <div class="enquiry-card__header">
                            <p class="overline">Primary CTA</p>
                            <h3>Book a lesson.</h3>
                            <p>A secure enquiry flow with source-page tracking for admin follow-up.</p>
                        </div>
                        <div class="cta-panel__actions">
                            <a href="<?= h($contactUrl) ?>" class="button button--solid">Open Enquiry Page</a>
                            <a href="<?= h($loginUrl) ?>" class="button button--outline">Portal Login</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="home-footer">
            <p>CandleCraft Academy</p>
            <a href="<?= h($loginUrl) ?>">Portal Login</a>
        </footer>
    </div>
</body>
</html>
