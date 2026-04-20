<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $enquiry
 * @var array<string, string> $enquirySubjects
 * @var array<string, string> $recaptcha
 * @var bool $requestAccount
 * @var string $sourcePage
 */
$this->disableAutoLayout();

$homeUrl = $this->Url->build('/');
$loginUrl = $this->Url->build(['controller' => 'Users', 'action' => 'login']);
$coursesUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index']);
$recaptchaSiteKey = (string)($recaptcha['siteKey'] ?? '');
$recaptchaMode = (string)($recaptcha['mode'] ?? 'disabled');
$recaptchaHelpText = (string)($recaptcha['helpText'] ?? '');
$captchaFooterNote = (string)($recaptcha['footerNote'] ?? 'Protected by anti-spam checks.');
$captchaRequired = $recaptchaMode === 'live';

$fieldError = static function (string $field) use ($enquiry): ?string {
    $errors = $enquiry->getError($field);
    if ($errors === []) {
        return null;
    }

    $first = array_shift($errors);

    return is_array($first) ? (string)array_shift($first) : (string)$first;
};

$describedBy = static function (array $ids): ?string {
    $filtered = array_values(array_filter($ids, static fn($id) => $id !== null && $id !== ''));

    return $filtered === [] ? null : implode(' ', $filtered);
};

$senderNameError = $fieldError('sender_name');
$senderEmailError = $fieldError('sender_email');
$senderPhoneError = $fieldError('sender_phone');
$subjectError = $fieldError('subject');
$declaredAgeError = $fieldError('declared_age');
$adultDeclarationError = $fieldError('self_declared_adult');
$messageTextError = $fieldError('message_text');
$captchaError = $fieldError('g-recaptcha-response');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CandleCraft Academy - Enquiry Form</title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['fonts', 'cake', 'home']) ?>
    <?php if ($recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="site-home site-contact">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="home-shell">
        <header class="hero-home hero-home--compact">
            <?= $this->element('public_nav', [
                'homeUrl' => $homeUrl,
                'coursesUrl' => $coursesUrl,
                'contactUrl' => $this->Url->build(['controller' => 'Pages', 'action' => 'contact']),
                'loginUrl' => $loginUrl,
                'showHomeLink' => true,
                'contactLabel' => 'Enquiry Form',
                'menuId' => 'contact-courses-menu',
            ]) ?>
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
                        'templates' => [
                            'inputContainer' => '{{content}}',
                            'inputContainerError' => '{{content}}',
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

                    <div class="enquiry-form__grid">
                        <div class="enquiry-field">
                            <label for="sender-name">Name *</label>
                            <?= $this->Form->text('sender_name', [
                                'id' => 'sender-name',
                                'placeholder' => 'Your name',
                                'required' => true,
                                'maxlength' => 500,
                                'autocomplete' => 'name',
                                'aria-describedby' => $describedBy([
                                    $senderNameError ? 'sender-name-error' : null,
                                ]),
                            ]) ?>
                            <?php if ($senderNameError): ?>
                                <p class="error-message" id="sender-name-error"><?= h($senderNameError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field">
                            <label for="sender-email">Email *</label>
                            <?= $this->Form->email('sender_email', [
                                'id' => 'sender-email',
                                'placeholder' => 'name@example.com',
                                'required' => true,
                                'autocomplete' => 'email',
                                'aria-describedby' => $describedBy([
                                    $senderEmailError ? 'sender-email-error' : null,
                                ]),
                            ]) ?>
                            <?php if ($senderEmailError): ?>
                                <p class="error-message" id="sender-email-error"><?= h($senderEmailError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field">
                            <label for="sender-phone">Phone *</label>
                            <?= $this->Form->text('sender_phone', [
                                'id' => 'sender-phone',
                                'placeholder' => 'Phone number',
                                'required' => true,
                                'maxlength' => 15,
                                'pattern' => '[0-9+\s\-()]+',
                                'autocomplete' => 'tel',
                                'aria-describedby' => $describedBy([
                                    'sender-phone-help',
                                    $senderPhoneError ? 'sender-phone-error' : null,
                                ]),
                            ]) ?>
                            <p class="field-help" id="sender-phone-help">Use numbers, spaces, brackets, dashes or a leading + sign.</p>
                            <?php if ($senderPhoneError): ?>
                                <p class="error-message" id="sender-phone-error"><?= h($senderPhoneError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field">
                            <label for="enquiry-subject">Enquiry Type *</label>
                            <?= $this->Form->select('subject', $enquirySubjects, [
                                'id' => 'enquiry-subject',
                                'empty' => 'Select an enquiry type',
                                'required' => true,
                                'aria-describedby' => $describedBy([
                                    $subjectError ? 'enquiry-subject-error' : null,
                                ]),
                            ]) ?>
                            <?php if ($subjectError): ?>
                                <p class="error-message" id="enquiry-subject-error"><?= h($subjectError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field enquiry-field--full">
                            <div style="padding: 20px; background: rgba(210, 154, 88, 0.1); border: 1px solid rgba(210, 154, 88, 0.3); border-radius: 12px;">
                                <div style="display: flex; align-items: flex-start; gap: 12px; color: #f5ecdf; font-family: var(--font-grown); font-size: 0.9rem; line-height: 1.5;">
                                    <?= $this->Form->checkbox('request_account', [
                                        'id' => 'request-account',
                                        'checked' => $requestAccount,
                                        'hiddenField' => false,
                                        'style' => 'width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; accent-color: var(--home-accent);',
                                        'aria-controls' => 'request-account-fields',
                                        'aria-expanded' => $requestAccount ? 'true' : 'false',
                                        'aria-describedby' => 'request-account-help',
                                    ]) ?>
                                    <div>
                                        <label for="request-account" style="display: inline; margin: 0; text-transform: none; letter-spacing: normal; color: #f5ecdf; font-size: 0.9rem; font-weight: 400;">
                                            I would like CandleCraft Academy to <strong style="color: var(--home-accent);">create a portal account for me</strong>.
                                        </label>
                                        <p class="field-help" id="request-account-help" style="margin: 8px 0 0; color: var(--home-text-muted);">
                                            Tick this box if you want the admin team to set up your customer portal access after reviewing this enquiry.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="request-account-fields" class="enquiry-field enquiry-field--full" <?= $requestAccount ? '' : 'hidden' ?>>
                            <div class="enquiry-form__grid" style="padding: 20px; background: rgba(210, 154, 88, 0.08); border: 1px solid rgba(210, 154, 88, 0.22); border-radius: 18px;">
                                <div class="enquiry-field" style="display: flex; flex-direction: column;">
                                    <p style="margin: 0 0 8px; color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em;">
                                        Customer Portal Request
                                    </p>
                                    <div style="padding: 16px; background: rgba(210, 154, 88, 0.1); border: 1px solid rgba(210, 154, 88, 0.22); border-radius: 12px; color: #f5ecdf; font-family: var(--font-grown); font-size: 0.95rem; line-height: 1.5; flex: 1;">
                                        Account applications submitted here are treated as <strong style="color: var(--home-accent);">customer portal requests</strong> and are provisioned through our legacy student profile flow.
                                    </div>
                                </div>

                                <div class="enquiry-field">
                                    <label for="declared-age">Your Age *</label>
                                    <?= $this->Form->number('declared_age', [
                                        'id' => 'declared-age',
                                        'value' => $this->request->getData('declared_age'),
                                        'min' => 1,
                                        'max' => 120,
                                        'placeholder' => 'Enter your age',
                                        'required' => $requestAccount,
                                        'aria-describedby' => $describedBy([
                                            'declared-age-help',
                                            $declaredAgeError ? 'declared-age-error' : null,
                                        ]),
                                    ]) ?>
                                    <p class="field-help" id="declared-age-help">
                                        Customer booking and payment access stay locked until an administrator confirms they are 18 or older.
                                    </p>
                                    <?php if ($declaredAgeError): ?>
                                        <p class="error-message" id="declared-age-error"><?= h($declaredAgeError) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="enquiry-field enquiry-field--full">
                                    <div style="display: flex; align-items: flex-start; gap: 12px; color: #f5ecdf; font-family: var(--font-grown); font-size: 0.9rem; line-height: 1.5;">
                                        <?= $this->Form->checkbox('self_declared_adult', [
                                            'id' => 'self-declared-adult',
                                            'checked' => (bool)$this->request->getData('self_declared_adult'),
                                            'hiddenField' => false,
                                            'style' => 'width: 20px; height: 20px; margin-top: 2px; flex-shrink: 0; accent-color: var(--home-accent);',
                                            'aria-describedby' => $describedBy([
                                                $adultDeclarationError ? 'self-declared-adult-error' : null,
                                            ]),
                                        ]) ?>
                                        <label for="self-declared-adult" style="display: inline; margin: 0; text-transform: none; letter-spacing: normal; color: #f5ecdf; font-size: 0.9rem; font-weight: 400;">
                                            I confirm that I am 18 years or older.
                                        </label>
                                    </div>
                                    <?php if ($adultDeclarationError): ?>
                                        <p class="error-message" id="self-declared-adult-error"><?= h($adultDeclarationError) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="enquiry-field enquiry-field--full">
                            <label for="message-text">Message *</label>
                            <?= $this->Form->textarea('message_text', [
                                'id' => 'message-text',
                                'rows' => 5,
                                'placeholder' => 'Your message here.',
                                'required' => true,
                                'aria-describedby' => $describedBy([
                                    $messageTextError ? 'message-text-error' : null,
                                ]),
                            ]) ?>
                            <?php if ($messageTextError): ?>
                                <p class="error-message" id="message-text-error"><?= h($messageTextError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field enquiry-field--full" role="group" aria-labelledby="captcha-label" aria-describedby="<?= h($describedBy([
                            'captcha-help',
                            $captchaError ? 'captcha-error' : null,
                        ]) ?? 'captcha-help') ?>">
                            <p id="captcha-label" style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 12px;">
                                CAPTCHA Verification<?= $captchaRequired ? ' *' : '' ?>
                            </p>

                            <?php if ($recaptchaSiteKey !== ''): ?>
                                <div class="g-recaptcha" style="display: inline-block;" data-sitekey="<?= h($recaptchaSiteKey) ?>"></div>
                                <p class="field-help" id="captcha-help" style="margin-top: 12px;">
                                    <?= h($recaptchaHelpText) ?>
                                </p>
                            <?php else: ?>
                                <p class="field-help" id="captcha-help">
                                    <?= h($recaptchaHelpText) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($captchaError): ?>
                                <p class="error-message" id="captcha-error"><?= h($captchaError) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="enquiry-form__footer">
                        <p class="enquiry-form__note"><?= h($captchaFooterNote) ?></p>
                        <?= $this->Form->button('Send Enquiry Form', [
                            'class' => 'btn-primary',
                            'style' => 'min-width: 260px; padding: 18px 40px; font-size: 1.1rem;',
                        ]) ?>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </main>

        <footer class="home-footer" role="contentinfo">
            <p>&copy; <?= date('Y') ?> CANDLECRAFT ACADEMY. All rights reserved.</p>
        </footer>
    </div>

    <script src="<?= $this->Url->build('/js/site-accessibility.js') ?>"></script>
    <script src="<?= $this->Url->build('/js/public-site.js') ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const requestAccount = document.getElementById('request-account');
        const requestAccountFields = document.getElementById('request-account-fields');
        const declaredAge = document.getElementById('declared-age');
        const selfDeclaredAdult = document.getElementById('self-declared-adult');
        const firstRequestField = requestAccountFields ? requestAccountFields.querySelector('input, select, textarea, button') : null;

        if (!requestAccount || !requestAccountFields) {
            return;
        }

        const syncRequestAccountFields = (moveFocus) => {
            const wantsAccount = requestAccount.checked;

            requestAccount.setAttribute('aria-expanded', wantsAccount ? 'true' : 'false');
            requestAccountFields.hidden = !wantsAccount;

            if (declaredAge) {
                declaredAge.required = wantsAccount;
            }

            if (wantsAccount && moveFocus && firstRequestField) {
                firstRequestField.focus();
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

        requestAccount.addEventListener('change', function () {
            syncRequestAccountFields(true);
        });

        syncRequestAccountFields(false);
    });
    </script>
</body>
</html>
