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
            <nav class="hero-nav" aria-label="Primary">
                <a href="<?= h($homeUrl) ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Tutoring</span>
                </a>
                <div class="hero-nav__links">
                    <a href="#overview">About</a>
                    <a href="<?= h($coursesUrl) ?>">Courses</a>
                    <a href="<?= h($contactUrl) ?>">Enquire</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Login</a>
                </div>
            </nav>

            <section class="hero-stage" aria-labelledby="landing-title">
                <div class="hero-stage__grain" aria-hidden="true"></div>
                <div class="hero-stage__halo hero-stage__halo--left" aria-hidden="true"></div>
                <div class="hero-stage__halo hero-stage__halo--right" aria-hidden="true"></div>
                <div class="hero-copy">
                    <p class="hero-copy__eyebrow">A Sanctuary For The Creative Soul.</p>
                    <h1 id="landing-title">CandleCraft Academy</h1>
                    <p class="hero-copy__body">
                        Discover the art of pottery and knitting in our intimate, expertly guided classes.
                    </p>
                    <div class="hero-copy__actions">
                        <a href="<?= h($coursesUrl) ?>" class="button button--solid">Browse Classes</a>
                        <a href="<?= h($contactUrl) ?>" class="button button--outline">Enquire Today</a>
                    </div>
                </div>
            </section>
        </header>

        <main id="main-content" tabindex="-1">

            <section id="overview" class="section--overview" aria-labelledby="overview-title">
                <h2 id="overview-title" class="section-title">Why CandleCraft Academy?</h2>
                <div class="overview-grid">
                    <article class="overview-card">
                        <h3>Expert Tutoring</h3>
                        <p>Learn pottery and knitting from experienced artisans who are passionate about sharing their craft with students of all ages and skill levels.</p>
                    </article>
                    <article class="overview-card">
                        <h3>Small Class Sizes</h3>
                        <p>Our intimate class settings ensure every student receives personalised attention and guidance throughout their creative journey.</p>
                    </article>
                    <article class="overview-card">
                        <h3>Flexible Scheduling</h3>
                        <p>Choose from a variety of class times that suit your schedule. Book online and manage your sessions with ease.</p>
                    </article>
                </div>

                <div class="overview-card overview-card--story">
                    <h3>Our Story</h3>
                    <p>CandleCraft Academy was founded with a simple mission: to create a warm, welcoming space where creativity flourishes. Whether you are shaping clay on the potter&rsquo;s wheel or weaving intricate patterns with yarn, our studio is your sanctuary.</p>
                    <p>We believe that the art of making &mdash; the feel of clay between your fingers, the rhythm of knitting needles &mdash; is a profoundly calming and rewarding experience. Join our community of makers and discover the joy of handcraft.</p>
                </div>
            </section>

            <section class="section--courses" aria-labelledby="courses-title">
                <h2 id="courses-title" class="section-title">What We Offer</h2>
                <div class="overview-grid">
                    <article class="overview-card">
                        <h3>Pottery Classes</h3>
                        <p>From hand-building to wheel throwing, explore the tactile art of ceramics. Suitable for beginners through to advanced potters.</p>
                    </article>
                    <article class="overview-card">
                        <h3>Knitting Workshops</h3>
                        <p>Master the fundamentals or refine your technique with our guided knitting sessions. Create beautiful pieces to take home.</p>
                    </article>
                    <article class="overview-card">
                        <h3>All Skill Levels</h3>
                        <p>Whether you&rsquo;re a complete beginner or an experienced crafter, we have classes tailored to your level and learning goals.</p>
                    </article>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <a href="<?= h($coursesUrl) ?>" class="button button--solid">View All Courses &amp; Book Online</a>
                </div>
            </section>

            <section class="section--cta" aria-labelledby="cta-title">
                <h2 id="cta-title" class="section-title">Ready to Start Creating?</h2>
                <p style="max-width: 580px; margin: 0 auto 32px; color: var(--home-text-muted); line-height: 1.8; font-size: 1.05rem;">
                    Join CandleCraft Academy today. Browse our classes, book a session online, and begin your creative journey.
                </p>
                <div class="hero-copy__actions">
                    <a href="<?= h($coursesUrl) ?>" class="button button--solid">Browse Classes</a>
                    <a href="<?= h($contactUrl) ?>" class="button button--outline">Send an Enquiry</a>
                    <a href="<?= h($loginUrl) ?>" class="button button--outline">Student Login</a>
                </div>
            </section>

        </main>

        <footer class="home-footer" role="contentinfo">
            <p>&copy; <?= date('Y') ?> CandleCraft Academy. All rights reserved.</p>
            <p>
                <a href="<?= h($contactUrl) ?>">Contact Us</a> &middot;
                <a href="<?= h($coursesUrl) ?>">Our Courses</a> &middot;
                <a href="<?= h($loginUrl) ?>">Portal Login</a>
            </p>
        </footer>
    </div>
</body>
</html>
