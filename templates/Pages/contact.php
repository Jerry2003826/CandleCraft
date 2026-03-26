<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $enquiry
 * @var array<string, string> $enquirySubjects
 * @var string $sourcePage
 * @var string $captchaQuestion
 */
$this->disableAutoLayout();

$homeUrl = $this->Url->build('/');
$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy - Contact</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['fonts', 'home']) ?>
</head>
<body class="site-home site-contact">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="home-shell">
        <header class="hero-home hero-home--compact">
            <nav class="hero-nav" aria-label="Primary">
                <a href="<?= h($homeUrl) ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Platform</span>
                </a>
                <div class="hero-nav__links">
                    <a href="<?= h($homeUrl) ?>#overview">About</a>
                    <a href="#enquiry">Enquiry</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Portal Login</a>
                </div>
            </nav>
        </header>

        <main id="main-content" tabindex="-1">
            <section class="section section--contact-page" aria-labelledby="contact-page-title">
                <div class="section-heading section-heading--contact">
                    <p class="overline">Contact / Enquiry</p>
                    <h1 class="section-heading__title" id="contact-page-title">Book a lesson.</h1>
                    <p class="section-heading__text">
                        A secure enquiry page for pottery and knitting bookings.
                    </p>
                </div>

                <div class="contact-shell contact-shell--page">
                    <div class="contact-copy">
                        <p class="overline">Tracked source</p>
                        <h2 id="enquiry-summary">Ask about lessons.</h2>
                        <p class="contact-copy__body">
                            Your enquiry is protected, routed to admin, and saved with its source page.
                        </p>

                        <div class="contact-highlights">
                            <div class="contact-highlight">
                                <span>Source page</span>
                                <strong><?= h($sourcePage) ?></strong>
                            </div>
                            <div class="contact-highlight">
                                <span>Security</span>
                                <strong>CAPTCHA, server validation, and anti-spam checks.</strong>
                            </div>
                            <div class="contact-highlight">
                                <span>Follow-up</span>
                                <strong>Admins can review and reply inside the dashboard.</strong>
                            </div>
                        </div>
                    </div>

                    <div class="enquiry-card" id="enquiry" aria-labelledby="enquiry-title">
                        <div class="enquiry-card__header">
                            <p class="overline">Lesson enquiry</p>
                            <h3 id="enquiry-title">Send a secure message.</h3>
                            <p id="enquiry-help">Share your details and booking question below.</p>
                        </div>

                        <div class="flash-region" aria-live="polite" aria-atomic="true">
                            <?= $this->Flash->render() ?>
                        </div>

                        <?= $this->Form->create($enquiry, [
                            'class' => 'enquiry-form',
                            'url' => [
                                'controller' => 'Pages',
                                'action' => 'contact',
                                '?' => ['from' => $sourcePage],
                                '#' => 'enquiry',
                            ],
                            'aria-describedby' => 'enquiry-help',
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
                                        'autocomplete' => 'name',
                                        'placeholder' => 'Your name',
                                        'required' => true,
                                    ]) ?>
                                    <?= $this->Form->error('sender_name') ?>
                                </div>

                                <div class="enquiry-field">
                                    <label for="sender-email">Email</label>
                                    <?= $this->Form->email('sender_email', [
                                        'id' => 'sender-email',
                                        'autocomplete' => 'email',
                                        'placeholder' => 'name@example.com',
                                        'required' => true,
                                    ]) ?>
                                    <?= $this->Form->error('sender_email') ?>
                                </div>

                                <div class="enquiry-field">
                                    <label for="sender-phone">Phone</label>
                                    <?= $this->Form->text('sender_phone', [
                                        'id' => 'sender-phone',
                                        'autocomplete' => 'tel',
                                        'inputmode' => 'tel',
                                        'placeholder' => 'Phone number',
                                        'required' => true,
                                    ]) ?>
                                    <?= $this->Form->error('sender_phone') ?>
                                </div>

                                <div class="enquiry-field">
                                    <label for="subject">Enquiry type</label>
                                    <?= $this->Form->select('subject', $enquirySubjects, [
                                        'id' => 'subject',
                                        'empty' => 'Select an enquiry type',
                                        'required' => true,
                                    ]) ?>
                                    <?= $this->Form->error('subject') ?>
                                </div>

                                <div class="enquiry-field enquiry-field--full">
                                    <label for="message-text">Message</label>
                                    <?= $this->Form->textarea('message_text', [
                                        'id' => 'message-text',
                                        'rows' => 5,
                                        'placeholder' => 'Lesson type, timing, or booking question.',
                                        'required' => true,
                                    ]) ?>
                                    <?= $this->Form->error('message_text') ?>
                                </div>

                                <div class="enquiry-field enquiry-field--full">
                                    <label for="captcha-answer">CAPTCHA: <?= h($captchaQuestion) ?></label>
                                    <p id="captcha-help" class="field-help">This quick question helps protect the form from spam.</p>
                                    <?= $this->Form->text('captcha_answer', [
                                        'id' => 'captcha-answer',
                                        'aria-describedby' => 'captcha-help',
                                        'inputmode' => 'numeric',
                                        'placeholder' => 'Enter the answer',
                                        'required' => true,
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
