<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $enquiry
 */
$this->disableAutoLayout();

$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
$homeUrl = $this->Url->build('/');

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
    <div class="home-shell">
        <header class="hero-home">
            <nav class="hero-nav">
                <a href="<?= h($homeUrl) ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Platform</span>
                </a>
                <div class="hero-nav__links">
                    <a href="#overview">About</a>
                    <a href="#contact">Ask</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Portal Login</a>
                </div>
            </nav>

            <section class="hero-stage">
                <div class="hero-stage__grain"></div>
                <div class="hero-stage__halo hero-stage__halo--left"></div>
                <div class="hero-stage__halo hero-stage__halo--right"></div>

                <aside class="hero-stage__meta">
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
                    <h1>CandleCraft Academy</h1>
                    <p class="hero-copy__body">
                        Creative lessons with a calm first impression.
                    </p>
                    <div class="hero-copy__actions">
                        <a href="#overview" class="button button--solid">About The Academy</a>
                    </div>
                </div>
            </section>
        </header>

        <main>
            <section id="overview" class="section section--overview">
                <div class="section-heading">
                    <p class="overline">About</p>
                    <h2>About CandleCraft Academy.</h2>
                    <p class="section-heading__text">
                        A creative academy for pottery and knitting lessons.
                    </p>
                </div>

                <div class="overview-grid">
                    <article class="overview-card overview-card--story">
                        <p class="overline">The academy</p>
                        <h3>Creative classes, made clear.</h3>
                        <p>
                            Families can learn about the academy, explore the lesson style, and move quickly into an enquiry.
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

            <section id="contact" class="section section--contact">
                <div class="contact-shell">
                    <div class="contact-copy">
                        <p class="overline">Enquiry</p>
                        <h2>Ask about lessons.</h2>
                        <p class="contact-copy__body">
                            Share your details and we will reply.
                        </p>
                    </div>

                    <div class="enquiry-card">
                        <div class="enquiry-card__header">
                            <p class="overline">Lesson enquiry</p>
                            <h3>Send a secure message.</h3>
                            <p>Tell us what you need below.</p>
                        </div>

                        <?= $this->Flash->render() ?>

                        <?= $this->Form->create($enquiry, [
                            'class' => 'enquiry-form',
                            'url' => ['controller' => 'Pages', 'action' => 'home', '#' => 'contact'],
                            'novalidate' => true,
                        ]) ?>
                            <?= $this->Form->hidden('source_page', ['value' => $sourcePage]) ?>

                            <div class="visually-hidden" aria-hidden="true">
                                <label for="enquiry-website">Website</label>
                                <?= $this->Form->text('website', [
                                    'id' => 'enquiry-website',
                                    'tabindex' => '-1',
                                    'autocomplete' => 'off',
                                ]) ?>
                            </div>

                            <div class="enquiry-form__grid">
                                <div class="enquiry-field">
                                    <label for="sender-name">Name</label>
                                    <?= $this->Form->text('sender_name', [
                                        'id' => 'sender-name',
                                        'placeholder' => 'Your name',
                                    ]) ?>
                                    <?= $this->Form->error('sender_name') ?>
                                </div>

                                <div class="enquiry-field">
                                    <label for="sender-email">Email</label>
                                    <?= $this->Form->email('sender_email', [
                                        'id' => 'sender-email',
                                        'placeholder' => 'name@example.com',
                                    ]) ?>
                                    <?= $this->Form->error('sender_email') ?>
                                </div>

                                <div class="enquiry-field">
                                    <label for="sender-phone">Phone</label>
                                    <?= $this->Form->text('sender_phone', [
                                        'id' => 'sender-phone',
                                        'placeholder' => 'Phone number',
                                    ]) ?>
                                    <?= $this->Form->error('sender_phone') ?>
                                </div>

                                <div class="enquiry-field">
                                    <label for="subject">Enquiry type</label>
                                    <?= $this->Form->select('subject', $enquirySubjects, [
                                        'id' => 'subject',
                                        'empty' => 'Select an enquiry type',
                                    ]) ?>
                                    <?= $this->Form->error('subject') ?>
                                </div>

                                <div class="enquiry-field enquiry-field--full">
                                    <label for="message-text">Message</label>
                                    <?= $this->Form->textarea('message_text', [
                                        'id' => 'message-text',
                                        'rows' => 5,
                                        'placeholder' => 'Lesson type, timing, or booking question.',
                                    ]) ?>
                                    <?= $this->Form->error('message_text') ?>
                                </div>

                                <div class="enquiry-field enquiry-field--full">
                                    <label for="captcha-answer">CAPTCHA: <?= h($captchaQuestion) ?></label>
                                    <?= $this->Form->text('captcha_answer', [
                                        'id' => 'captcha-answer',
                                        'inputmode' => 'numeric',
                                        'placeholder' => 'Enter the answer',
                                    ]) ?>
                                    <?= $this->Form->error('captcha_answer') ?>
                                </div>
                            </div>

                            <div class="enquiry-form__footer">
                                <p class="enquiry-form__note">
                                    Protected by CAPTCHA and anti-spam checks.
                                </p>
                                <?= $this->Form->button('Send Enquiry', ['class' => 'button button--solid']) ?>
                            </div>
                        <?= $this->Form->end() ?>
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
