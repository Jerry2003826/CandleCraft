<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $enquiry
 * @var array<string, string> $enquirySubjects
 * @var array<string, string> $recaptcha
 * @var string $sourcePage
 * @var bool $requestAccount
 */
$this->disableAutoLayout();

$siteName = $this->Cms->text('global', 'branding.site_name', 'CandleCraft Academy');
$cmsFavicon = $this->Cms->image('global', 'branding.favicon_image');

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
    if ($errors === []) return null;
    $first = array_shift($errors);
    return is_array($first) ? (string)array_shift($first) : (string)$first;
};

$senderNameError = $fieldError('sender_name');
$senderEmailError = $fieldError('sender_email');
$senderPhoneError = $fieldError('sender_phone');
$subjectError = $fieldError('subject');
$messageTextError = $fieldError('message_text');
$studentNameError = $fieldError('student_name');
$studentDobError = $fieldError('student_dob');
$classTypeError = $fieldError('class_type');
$declaredAgeError = $fieldError('declared_age');
$selfDeclaredAdultError = $fieldError('self_declared_adult');
$captchaError = $fieldError('g-recaptcha-response');

$serverErrors = array_filter([
    'sender-name'         => ['Name', $senderNameError],
    'sender-email'        => ['Email', $senderEmailError],
    'sender-phone'        => ['Phone', $senderPhoneError],
    'enquiry-subject'     => ['Enquiry Type', $subjectError],
    'student-name'        => ['Student Name', $studentNameError],
    'student-dob'         => ['Student Date of Birth', $studentDobError],
    'class-type'          => ['Class Type', $classTypeError],
    'message-text'        => ['Message', $messageTextError],
    'declared-age'        => ['Age', $declaredAgeError],
    'self-declared-adult' => ['I am 18 or older', $selfDeclaredAdultError],
    'captcha-label'       => ['CAPTCHA', $captchaError],
], static fn($e) => $e[1] !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($siteName) ?> &mdash; <?= h($this->Cms->text('contact', 'intro.title', 'Enquiry Form')) ?></title>
    <link rel="icon" type="image/png" href="<?= h($cmsFavicon ?? $this->Url->build('/favicon.png')) ?>">
    <?= $this->Html->css(['fonts', 'cake']) ?>
    <?= $this->Html->css('home', ['timestamp' => 'force']) ?>
    <style>
        .enquiry-form input.is-invalid,
        .enquiry-form select.is-invalid,
        .enquiry-form textarea.is-invalid,
        .enquiry-form__form.was-validated input:invalid,
        .enquiry-form__form.was-validated select:invalid,
        .enquiry-form__form.was-validated textarea:invalid {
            border-color: #ff5c4d !important;
            background: #fff3f1 !important;
            box-shadow: 0 0 0 3px rgba(255, 92, 77, 0.22) !important;
        }

        .enquiry-error-summary {
            margin-bottom: 24px !important;
            padding: 16px 20px !important;
            background: rgba(180, 30, 20, 0.45) !important;
            border: 2px solid #ff5c4d !important;
            border-radius: 12px !important;
            color: #fff0ee !important;
        }

        .enquiry-error-summary a {
            color: #fff0ee !important;
            font-weight: 700 !important;
        }
    </style>
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
                'showHomeLink' => false,
                'aboutUrl' => $homeUrl . '#main-content',
                'showAboutLink' => false,
                'contactLabel' => $this->Cms->text('global', 'nav.cta_label', 'Enquire'),
                'menuId' => 'contact-courses-menu',
            ]) ?>
        </header>

        <div class="section-heading" style="text-align: center; padding: 60px 20px 20px;">
            <p class="overline" style="font-family: var(--font-grown); color: #f5ecdf; letter-spacing: 0.3em; margin-bottom: 12px; font-size: 0.8rem;">
                <?= h($siteName) ?>
            </p>
            <h1 style="font-family: var(--font-grown); font-size: clamp(2.5rem, 5vw, 4rem); color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0; line-height: 1;">
                <?= h($this->Cms->text('contact', 'intro.title', 'Enquiry Form')) ?>
            </h1>
            <div style="width: 60px; height: 2px; background: var(--home-accent); margin: 24px auto 0; opacity: 0.6;"></div>
        </div>

        <main id="main-content" style="max-width: 900px; margin: 0 auto 80px; padding: 0 20px;">
            <div class="enquiry-card" style="background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 40px; box-shadow: var(--home-shadow);">
                <p class="enquiry-intro">
                    <?= h($this->Cms->text('contact', 'intro.body', 'Use the enquiry form below and someone from our team will be in touch shortly.')) ?>
                </p>

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
                        'class' => 'enquiry-form__form',
                        'novalidate' => true,
                    ]) ?>

                    <?php if (!empty($serverErrors)): ?>
                        <div class="enquiry-error-summary" role="alert" tabindex="-1">
                            <p class="enquiry-error-summary__title">Please fix the following errors before submitting:</p>
                            <ul class="enquiry-error-summary__list">
                                <?php foreach ($serverErrors as $id => [$label, $msg]): ?>
                                    <li><a href="#<?= h($id) ?>"><?= h($label) ?></a>: <?= h($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div id="js-error-summary" class="enquiry-error-summary" role="alert" tabindex="-1" hidden>
                        <p class="enquiry-error-summary__title">Please fix the following errors before submitting:</p>
                        <ul class="enquiry-error-summary__list" id="js-error-list"></ul>
                    </div>

                    <?= $this->Form->hidden('source_page', ['value' => $sourcePage]) ?>
                    <?= $this->Form->hidden('form_type', ['value' => 'enquiry']) ?>

                    <!-- Always visible fields -->
                    <div class="enquiry-form__grid">
                        <div class="enquiry-field">
                            <label for="sender-name">Name *</label>
                            <?= $this->Form->text('sender_name', [
                                'id' => 'sender-name',
                                'placeholder' => 'Your name',
                                'required' => true,
                                'maxlength' => 500,
                                'autocomplete' => 'name',
                                'class' => $senderNameError ? 'is-invalid' : '',
                            ]) ?>
                            <?php if ($senderNameError): ?>
                                <p class="error-message"><?= h($senderNameError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field">
                            <label for="sender-email">Email *</label>
                            <?= $this->Form->email('sender_email', [
                                'id' => 'sender-email',
                                'placeholder' => 'name@example.com',
                                'required' => true,
                                'autocomplete' => 'email',
                                'class' => $senderEmailError ? 'is-invalid' : '',
                            ]) ?>
                            <?php if ($senderEmailError): ?>
                                <p class="error-message"><?= h($senderEmailError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field">
                            <label for="sender-phone">Phone *</label>
                            <?= $this->Form->text('sender_phone', [
                                'id' => 'sender-phone',
                                'placeholder' => '04xx xxx xxx or +61 4xx xxx xxx',
                                'required' => true,
                                'maxlength' => 20,
                                'pattern' => '\+?[0-9][0-9\s\-\(\)]{5,14}',
                                'title' => 'Use numbers, spaces, brackets, dashes or a leading + sign.',
                                'autocomplete' => 'tel',
                                'class' => $senderPhoneError ? 'is-invalid' : '',
                            ]) ?>
                            <p class="field-help">Use numbers, spaces, brackets, dashes or a leading + sign.</p>
                            <?php if ($senderPhoneError): ?>
                                <p class="error-message"><?= h($senderPhoneError) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="enquiry-field enquiry-field--subject">
                            <label for="enquiry-subject">Enquiry Type *</label>
                            <?= $this->Form->select('subject', $enquirySubjects, [
                                'id' => 'enquiry-subject',
                                'empty' => 'Select an enquiry type',
                                'required' => true,
                                'class' => $subjectError ? 'is-invalid' : '',
                            ]) ?>
                            <?php if ($subjectError): ?>
                                <p class="error-message"><?= h($subjectError) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="enquiry-field enquiry-field--full enquiry-field--checkbox-align" style="margin-top: 20px;">
                        <label class="enquiry-checkbox" for="request-account">
                            <?= $this->Form->checkbox('request_account', [
                                'id' => 'request-account',
                                'checked' => $requestAccount,
                                'hiddenField' => false,
                                'aria-controls' => 'request-account-fields',
                                'aria-expanded' => $requestAccount ? 'true' : 'false',
                            ]) ?>
                            <span>Request a customer portal account</span>
                        </label>
                    </div>

                    <div id="request-account-fields" <?= $requestAccount ? '' : 'hidden' ?>>
                        <div class="enquiry-form__grid" style="margin-top: 20px;">
                            <div class="enquiry-field">
                                <label for="declared-age">Age *</label>
                                <?= $this->Form->number('declared_age', [
                                    'id' => 'declared-age',
                                    'min' => 1,
                                    'max' => 120,
                                    'class' => $declaredAgeError ? 'is-invalid' : '',
                                    'disabled' => !$requestAccount,
                                ]) ?>
                                <?php if ($declaredAgeError): ?>
                                    <p class="error-message"><?= h($declaredAgeError) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="enquiry-field enquiry-field--checkbox-align">
                                <label class="enquiry-checkbox" for="self-declared-adult">
                                    <?= $this->Form->checkbox('self_declared_adult', [
                                        'id' => 'self-declared-adult',
                                        'checked' => (bool)$this->request->getData('self_declared_adult'),
                                        'hiddenField' => false,
                                        'class' => $selfDeclaredAdultError ? 'is-invalid' : '',
                                        'disabled' => !$requestAccount,
                                    ]) ?>
                                    <span>I am 18 or older</span>
                                </label>
                                <?php if ($selfDeclaredAdultError): ?>
                                    <p class="error-message"><?= h($selfDeclaredAdultError) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

	                    <!-- Book a Class fields -->
                    <div id="book-a-class-fields" hidden>
                        <div class="enquiry-form__grid" style="margin-top: 20px;">
                            <div class="enquiry-field">
                                <label for="student-name">Student Name * <span class="field-info-icon" data-tip="Name of the student being enrolled" role="img" aria-label="Name of the person attending the classes" tabindex="0">i</span></label>
                                <?= $this->Form->text('student_name', [
                                    'id' => 'student-name',
                                    'placeholder' => 'Student full name',
                                    'class' => $studentNameError ? 'is-invalid' : '',
                                ]) ?>
                                <?php if ($studentNameError): ?>
                                    <p class="error-message"><?= h($studentNameError) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="enquiry-field">
                                <label for="student-dob">Student Date of Birth *</label>
                                <?= $this->Form->date('student_dob', [
                                    'id' => 'student-dob',
                                    'max' => date('Y-m-d'),
                                    'class' => $studentDobError ? 'is-invalid' : '',
                                ]) ?>
                                <?php if ($studentDobError): ?>
                                    <p class="error-message"><?= h($studentDobError) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="enquiry-field enquiry-field--full">
                                <label for="class-type">Class Type *</label>
                                <?= $this->Form->select('class_type', [
                                    'pottery' => 'Pottery',
                                    'knitting' => 'Knitting',
                                    'both' => 'Both',
                                ], [
                                    'id' => 'class-type',
                                    'empty' => 'Select a class type',
                                    'class' => $classTypeError ? 'is-invalid' : '',
                                ]) ?>
                                <?php if ($classTypeError): ?>
                                    <p class="error-message"><?= h($classTypeError) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Message field (always visible) -->
                    <div class="enquiry-field enquiry-field--full" style="margin-top: 20px;">
                        <label for="message-text">Message *</label>
                        <?= $this->Form->textarea('message_text', [
                            'id' => 'message-text',
                            'maxlength' => 1000,
                            'rows' => 5,
                            'placeholder' => 'Your message here.',
                            'required' => true,
                            'class' => $messageTextError ? 'is-invalid' : '',
                        ]) ?>
                        <?php if ($messageTextError): ?>
                            <p class="error-message"><?= h($messageTextError) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- CAPTCHA -->
                    <div class="enquiry-field enquiry-field--full" style="margin-top: 20px;">
                        <p id="captcha-label" class="enquiry-section-label">
                            CAPTCHA Verification<?= $captchaRequired ? ' *' : '' ?>
                        </p>
                        <?php if ($recaptchaSiteKey !== ''): ?>
                            <div class="g-recaptcha" data-sitekey="<?= h($recaptchaSiteKey) ?>" aria-labelledby="captcha-label" aria-describedby="captcha-help<?= $captchaError ? ' captcha-error' : '' ?>"></div>
                        <?php endif; ?>
                        <p class="field-help" id="captcha-help"><?= h($recaptchaHelpText) ?></p>
                        <?php if ($captchaError): ?>
                            <p class="error-message" id="captcha-error" role="alert"><?= h($captchaError) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="enquiry-form__footer" style="margin-top: 24px;">
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
            <p><?= h($this->Cms->text('global', 'branding.copyright_text', '© ' . date('Y') . ' CANDLECRAFT ACADEMY. All rights reserved.')) ?></p>
        </footer>
    </div>

    <script src="<?= $this->Url->build('/js/site-accessibility.js') ?>"></script>
    <script src="<?= $this->Url->build('/js/public-site.js') ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const subjectSelect = document.getElementById('enquiry-subject');
        const bookFields = document.getElementById('book-a-class-fields');

        const studentName = document.getElementById('student-name');
        const studentDob = document.getElementById('student-dob');
        const classType = document.getElementById('class-type');
        const requestAccount = document.getElementById('request-account');
        const requestAccountFields = document.getElementById('request-account-fields');
        const declaredAge = document.getElementById('declared-age');
        const selfDeclaredAdult = document.getElementById('self-declared-adult');
        const senderPhone = document.getElementById('sender-phone');
        const form = document.querySelector('.enquiry-form__form');

        function updateInvalidState(field) {
            if (!field || !field.matches('input, select, textarea')) return;
            if (field === senderPhone) validatePhoneNumber();
            field.classList.toggle('is-invalid', !field.validity.valid);
        }

        if (senderPhone) {
            senderPhone.addEventListener('input', function () {
                // Permit a single leading "+" plus digits, spaces, brackets and dashes.
                const pos = this.selectionStart;
                const before = this.value;
                this.value = before.replace(/[^0-9+\s\-()]/g, '').slice(0, 20);
                if (this.value !== before) {
                    const diff = before.length - this.value.length;
                    const newPos = Math.max(0, pos - diff);
                    this.setSelectionRange(newPos, newPos);
                }
            });
        }

        function validatePhoneNumber() {
            if (!senderPhone) return;
            const value = senderPhone.value;
            senderPhone.setCustomValidity(value === '' || /^\+?[0-9][0-9\s\-\(\)]{5,14}$/.test(value)
                ? ''
                : 'Use numbers, spaces, brackets, dashes or a leading + sign.');
        }

        function updateFields() {
            const val = subjectSelect.value.toLowerCase();
            const isBooking = val.includes('book');
            const isRequestingAccount = requestAccount && requestAccount.checked;

            bookFields.hidden = !isBooking;
            if (requestAccountFields) requestAccountFields.hidden = !isRequestingAccount;
            if (requestAccount) requestAccount.setAttribute('aria-expanded', isRequestingAccount ? 'true' : 'false');

            if (studentName) studentName.required = isBooking;
            if (studentDob) studentDob.required = isBooking;
            if (classType) classType.required = isBooking;
            if (declaredAge) {
                declaredAge.required = isRequestingAccount;
                declaredAge.disabled = !isRequestingAccount;
                if (!isRequestingAccount) {
                    declaredAge.classList.remove('is-invalid');
                } else if (form && form.classList.contains('was-validated')) {
                    updateInvalidState(declaredAge);
                }
            }
            if (selfDeclaredAdult) {
                selfDeclaredAdult.required = isRequestingAccount;
                selfDeclaredAdult.disabled = !isRequestingAccount;
                if (!isRequestingAccount) {
                    selfDeclaredAdult.classList.remove('is-invalid');
                } else if (form && form.classList.contains('was-validated')) {
                    updateInvalidState(selfDeclaredAdult);
                }
            }
        }

        subjectSelect.addEventListener('change', updateFields);
        if (requestAccount) requestAccount.addEventListener('change', updateFields);
        if (form) {
            const jsSummary = document.getElementById('js-error-summary');
            const jsErrorList = document.getElementById('js-error-list');

            const fieldLabels = {
                'sender-name':        'Name',
                'sender-email':       'Email',
                'sender-phone':       'Phone',
                'enquiry-subject':    'Enquiry Type',
                'student-name':       'Student Name',
                'student-dob':        'Student Date of Birth',
                'class-type':         'Class Type',
                'message-text':       'Message',
                'declared-age':       'Age',
                'self-declared-adult':'I am 18 or older',
            };

            function isFieldVisible(field) {
                return !field.disabled && !field.closest('[hidden]');
            }

            function showErrorSummary(invalidFields) {
                if (!jsSummary || !jsErrorList) return;
                jsErrorList.innerHTML = '';
                invalidFields.forEach(function (field) {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = '#' + field.id;
                    a.textContent = (fieldLabels[field.id] || field.name);
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        field.focus();
                    });
                    li.appendChild(a);
                    li.appendChild(document.createTextNode(': ' + field.validationMessage));
                    jsErrorList.appendChild(li);
                });
                jsSummary.hidden = false;
                jsSummary.focus();
            }

            function hideErrorSummary() {
                if (jsSummary) jsSummary.hidden = true;
            }

            form.setAttribute('novalidate', '');

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                validatePhoneNumber();
                form.classList.add('was-validated');

                const invalidFields = [];
                form.querySelectorAll('input, select, textarea').forEach(function (field) {
                    if (!isFieldVisible(field)) return;
                    updateInvalidState(field);
                    if (!field.checkValidity()) invalidFields.push(field);
                });

                if (invalidFields.length > 0) {
                    showErrorSummary(invalidFields);
                } else {
                    hideErrorSummary();
                    form.submit();
                }
            });

            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.addEventListener('input', function () { updateInvalidState(field); });
                field.addEventListener('change', function () { updateInvalidState(field); });
            });
        }
        updateFields();

        document.querySelectorAll('.enquiry-toast').forEach(function (toast) {
            const DURATION = 5000;
            const bar = toast.querySelector('.enquiry-toast__bar');
            if (bar) bar.style.animationDuration = DURATION + 'ms';

            const closeBtn = toast.querySelector('.enquiry-toast__close');
            function dismiss() {
                toast.classList.add('enquiry-toast--out');
                toast.addEventListener('animationend', function () { toast.remove(); }, { once: true });
            }
            if (closeBtn) closeBtn.addEventListener('click', dismiss);
            setTimeout(dismiss, DURATION);
        });
    });
    </script>

    <div class="enquiry-toast-region" aria-live="polite">
        <?= $this->Flash->render('enquiry', ['element' => 'flash/enquiry_toast']) ?>
    </div>
</body>
</html>
