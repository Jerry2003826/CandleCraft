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
    <?= $this->Html->css(['fonts', 'cake', 'admin', 'home']) ?>
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

        <main id="main-content" style="max-width: 1400px; margin: 30px auto; padding: 0 20px;">

            <div class="card" id="enquiry">
                <div class="card-header">
                    <h3 style="font-family: var(--font-grown); text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">Enquiry</h3>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-muted); margin-bottom: 8px;">Use the form below and someone from our team will be in touch shortly!</p>

                    <?= $this->Flash->render() ?>

                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                        <span style="color: var(--primary);">*</span> Required fields
                    </p>

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

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 16px;">

                        <div class="form-group">
                            <label>Name <span style="color: var(--primary);">*</span></label>
                            <?= $this->Form->text('sender_name', [
                                'placeholder' => 'Your name',
                                'required' => true,
                                'maxlength' => 500,
                            ]) ?>
                            <?= $this->Form->error('sender_name') ?>
                        </div>

                        <div class="form-group">
                            <label>Email <span style="color: var(--primary);">*</span></label>
                            <?= $this->Form->email('sender_email', [
                                'placeholder' => 'name@example.com',
                                'required' => true,
                            ]) ?>
                            <?= $this->Form->error('sender_email') ?>
                        </div>

                        <div class="form-group">
                            <label>Phone <span style="color: var(--primary);">*</span></label>
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
                            <label>Enquiry type <span style="color: var(--primary);">*</span></label>
                            <?= $this->Form->select('subject', $enquirySubjects, [
                                'empty' => 'Select an enquiry type',
                                'required' => true,
                            ]) ?>
                            <?= $this->Form->error('subject') ?>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Message <span style="color: var(--primary);">*</span></label>
                            <?= $this->Form->textarea('message_text', [
                                'rows' => 5,
                                'placeholder' => 'Your message here.',
                                'required' => true,
                            ]) ?>
                            <?= $this->Form->error('message_text') ?>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>CAPTCHA <span style="color: var(--primary);">*</span></label>
                            <div class="g-recaptcha" data-sitekey="6Ld-GZosAAAAAO_CNYc_Ax-DYUAW6AHQg1glDTeM"></div>
                            <?= $this->Form->error('captcha_answer') ?>
                            <?php if ($enquiry->getError('g-recaptcha-response')): ?>
                                <p style="display: inline-block; margin-top: 6px; padding: 6px 12px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; color: #721c24; font-size: 0.85rem;">
                                    Please complete the CAPTCHA.
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 24px;">
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Protected by CAPTCHA and anti-spam checks.</p>
                        <?= $this->Form->button('Send Enquiry', ['class' => 'btn btn-primary']) ?>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>

        </main>

        <footer class="home-footer">
            <p>CandleCraft Academy</p>
            <a href="<?= h($loginUrl) ?>">Portal Login</a>
        </footer>
    </div>
</body>
</html>