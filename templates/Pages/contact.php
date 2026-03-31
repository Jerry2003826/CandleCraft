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
$coursesUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy - Contact</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['fonts', 'cake', 'home']) ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="site-home site-contact">
    <div class="home-shell">
        <header class="hero-home hero-home--compact">
            <nav class="hero-nav" aria-label="Primary">
                <a href="<?= h($homeUrl) ?>" class="brand-mark">
                    <span class="brand-mark__title">CandleCraft Academy</span>
                    <span class="brand-mark__subtitle">Pottery &amp; Knitting Tutoring</span>
                </a>
                <div class="hero-nav__links">
                    <a href="<?= h($homeUrl) ?>">Home</a>
                    <a href="<?= h($coursesUrl) ?>">Courses</a>
                    <a href="#enquiry">Enquire</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Login</a>
                </div>
            </nav>
        </header>

        <div class="section-heading" style="text-align: center; padding: 60px 20px 20px;">
            <p class="overline" style="font-family: var(--font-grown); color: var(--home-accent); letter-spacing: 0.3em; margin-bottom: 12px; font-size: 0.8rem;">
                CandleCraft Academy
            </p>
            <h1 style="font-family: var(--font-grown); font-size: clamp(2.5rem, 5vw, 4rem); color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0; line-height: 1;">
                Enquiry
            </h1>
            <div style="width: 60px; height: 2px; background: var(--home-accent); margin: 24px auto 0; opacity: 0.6;"></div>
        </div>

        <main id="main-content" style="max-width: 900px; margin: 0 auto 80px; padding: 0 20px;">

            <div class="enquiry-card" id="enquiry" style="background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 40px; box-shadow: var(--home-shadow);">
                
                <p style="font-family: var(--font-grown); color: var(--home-text-muted); text-align: center; margin-bottom: 30px; font-size: 1.4rem;">
                    Use the form below and someone from our team will be in touch shortly!
                </p>

                <?= $this->Flash->render() ?>

                <div class="enquiry-form">
                    <?= $this->Form->create($enquiry, [
                        'url' => [
                            'controller' => 'Pages',
                            'action' => 'contact',
                            '?' => ['from' => $sourcePage],
                            '#' => 'enquiry',
                        ],
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

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px 20px;">

                        <div class="form-group">
                            <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Name *</label>
                            <?= $this->Form->text('sender_name', [
                                'placeholder' => 'Your name',
                                'required' => true,
                                'maxlength' => 500,
                            ]) ?>
                            <?= $this->Form->error('sender_name') ?>
                        </div>

                        <div class="form-group">
                            <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Email *</label>
                            <?= $this->Form->email('sender_email', [
                                'placeholder' => 'name@example.com',
                                'required' => true,
                            ]) ?>
                            <?= $this->Form->error('sender_email') ?>
                        </div>

                        <div class="form-group">
                            <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Phone *</label>
                            <?= $this->Form->text('sender_phone', [
                                'placeholder' => 'Phone number',
                                'required' => true,
                                'maxlength' => 15,
                                'pattern' => '[0-9+\s\-()]+',
                                'title' => 'Numbers only please',
                            ]) ?>
                            <?= $this->Form->error('sender_phone') ?>
                        </div>

                        <div class="form-group">
                            <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Enquiry type *</label>
                            <?= $this->Form->select('subject', $enquirySubjects, [
                                'empty' => 'Select an enquiry type',
                                'required' => true,
                            ]) ?>
                            <?= $this->Form->error('subject') ?>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Message *</label>
                            <?= $this->Form->textarea('message_text', [
                                'rows' => 5,
                                'placeholder' => 'Your message here.',
                                'required' => true,
                            ]) ?>
                            <?= $this->Form->error('message_text') ?>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1; text-align: center;">
                            <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 12px;">CAPTCHA Verification *</label>
                            <div class="g-recaptcha" style="display: inline-block;" data-sitekey="6Ld-GZosAAAAAO_CNYc_Ax-DYUAW6AHQg1glDTeM"></div>
                            <?= $this->Form->error('captcha_answer') ?>
                            <?php if ($enquiry->getError('g-recaptcha-response')): ?>
                                <p style="display: block; margin-top: 10px; padding: 6px 12px; background: rgba(138, 48, 44, 0.4); border: 1px solid rgba(225, 110, 103, 0.34); border-radius: 8px; color: #ffd8d4; font-size: 0.85rem;">
                                    Please complete the CAPTCHA.
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 34px; flex-wrap: wrap; gap: 20px;">
                        <p style="margin: 0; font-size: 0.85rem; color: var(--home-text-muted); font-family: var(--font-grown);">Protected by CAPTCHA and anti-spam checks.</p>
                        <?= $this->Form->button('Send Enquiry', ['class' => 'btn-primary', 'style' => 'min-width: 200px;']) ?>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </main>

      
        <footer class="home-footer" role="contentinfo">
            <p>&copy; <?= date('Y') ?> CANDLECRAFT ACADEMY. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>