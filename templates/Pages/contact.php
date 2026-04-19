<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $enquiry
 * @var array<string, string> $enquirySubjects
 * @var bool $requestAccount
 * @var string $sourcePage
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
    <title>CandleCraft Academy - Enquiry Form</title>
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
                    <div class="nav-dropdown">
                        <a href="<?= h($coursesUrl) ?>">Courses</a>
                        <div class="nav-dropdown__menu">
                            <div class="nav-dropdown__menu-inner">
                                <a href="<?= h($coursesUrl) ?>?type=pottery">Pottery</a>
                                <a href="<?= h($coursesUrl) ?>?type=knitting">Knitting</a>
                            </div>
                        </div>
                    </div>
                    <a href="#enquiry">Enquiry Form</a>
                    <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Login</a>
                </div>
            </nav>
        </header>

        <div class="section-heading" style="text-align: center; padding: 60px 20px 20px;">
            <p class="overline" style="font-family: var(--font-grown); color: var(--home-accent); letter-spacing: 0.3em; margin-bottom: 12px; font-size: 0.8rem;">
                CandleCraft Academy
            </p>
            <h1 style="font-family: var(--font-grown); font-size: clamp(2.5rem, 5vw, 4rem); color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0; line-height: 1;">
                Enquiry Form
            </h1>
            <div style="width: 60px; height: 2px; background: var(--home-accent); margin: 24px auto 0; opacity: 0.6;"></div>
        </div>

        <main id="main-content" style="max-width: 900px; margin: 0 auto 80px; padding: 0 20px;">
            <div class="enquiry-card" id="enquiry" style="background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 40px; box-shadow: var(--home-shadow);">
                
                <p style="font-family: var(--font-grown); color: var(--home-text-muted); text-align: center; margin-bottom: 30px; font-size: 1.4rem;">
                    Use the enquiry form below and someone from our team will be in touch shortly.
                </p>

                <?= $this->Flash->render('enquiry') ?>

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
                    <?= $this->Form->hidden('form_type', ['value' => 'enquiry']) ?>

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
                            <div style="padding: 20px; background: rgba(210, 154, 88, 0.1); border: 1px solid rgba(210, 154, 88, 0.3); border-radius: 12px;">
                                <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; color: #f5ecdf; font-family: var(--font-grown); font-size: 0.9rem; line-height: 1.5;">
                                    <?= $this->Form->checkbox('request_account', [
                                        'id' => 'request-account',
                                        'checked' => $requestAccount,
                                        'hiddenField' => false,
                                        'style' => 'width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; accent-color: var(--home-accent);',
                                    ]) ?>
                                    <span>
                                        I would like CandleCraft Academy to <strong style="color: var(--home-accent);">create a portal account for me</strong>.
                                        <br>
                                        <small style="color: var(--home-text-muted);">
                                            Tick this box if you want the admin team to set up your customer portal access after reviewing this enquiry.
                                        </small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div id="request-account-fields" style="grid-column: 1 / -1; display: none;">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px 20px; padding: 20px; background: rgba(210, 154, 88, 0.08); border: 1px solid rgba(210, 154, 88, 0.22); border-radius: 18px;">
                                <div class="form-group" style="display: flex; flex-direction: column;">
                                    <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Customer portal request</label>
                                    <div style="padding: 16px; background: rgba(210, 154, 88, 0.1); border: 1px solid rgba(210, 154, 88, 0.22); border-radius: 12px; color: #f5ecdf; font-family: var(--font-grown); font-size: 0.95rem; line-height: 1.5; flex: 1;">
                                        Account applications submitted here are treated as <strong style="color: var(--home-accent);">customer portal requests</strong> and are provisioned through our legacy student profile flow.
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Your age *</label>
                                    <?= $this->Form->number('declared_age', [
                                        'id' => 'declared-age',
                                        'value' => $this->request->getData('declared_age'),
                                        'min' => 1,
                                        'max' => 120,
                                        'placeholder' => 'Enter your age',
                                        'required' => $requestAccount,
                                    ]) ?>
                                    <?= $this->Form->error('declared_age') ?>
                                    <small style="display: block; margin-top: 10px; color: var(--home-text-muted); font-family: var(--font-grown);">
                                        Customer booking and payment access stay locked until an administrator confirms they are 18 or older.
                                    </small>
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; color: #f5ecdf; font-family: var(--font-grown); font-size: 0.9rem; line-height: 1.5;">
                                        <?= $this->Form->checkbox('self_declared_adult', [
                                            'id' => 'self-declared-adult',
                                            'checked' => (bool)$this->request->getData('self_declared_adult'),
                                            'hiddenField' => false,
                                            'style' => 'width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; accent-color: var(--home-accent);',
                                        ]) ?>
                                        <span>I confirm that I am 18 years or older.</span>
                                    </label>
                                    <?= $this->Form->error('self_declared_adult') ?>
                                </div>
                            </div>
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
                        <?= $this->Form->button('Send Enquiry Form', ['class' => 'btn-primary', 'style' => 'min-width: 260px; padding: 18px 40px; font-size: 1.1rem;']) ?>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </main>

      
        <footer class="home-footer" role="contentinfo">
            <p>&copy; <?= date('Y') ?> CANDLECRAFT ACADEMY. All rights reserved.</p>
        </footer>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const requestAccount = document.getElementById('request-account');
        const requestAccountFields = document.getElementById('request-account-fields');
        const declaredAge = document.getElementById('declared-age');
        const selfDeclaredAdult = document.getElementById('self-declared-adult');

        if (!requestAccount || !requestAccountFields) {
            return;
        }

        const syncRequestAccountFields = () => {
            const wantsAccount = requestAccount.checked;
            requestAccountFields.style.display = wantsAccount ? 'block' : 'none';

            if (declaredAge) {
                declaredAge.required = wantsAccount;
            }

            if (!wantsAccount) {
                if (declaredAge) {
                    declaredAge.value = '';
                }

                if (selfDeclaredAdult) {
                    selfDeclaredAdult.checked = false;
                }
            }
        };

        requestAccount.addEventListener('change', syncRequestAccountFields);
        syncRequestAccountFields();
    });
    </script>
</body>
</html>
