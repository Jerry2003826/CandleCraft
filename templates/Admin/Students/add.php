<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 * @var bool $createPortalAccount
 * @var array<string, string> $portalAccount
 * @var array<string, mixed> $portalAccountErrors
 */
$this->assign('title', 'Add Student');
$portalError = static function (array $errors, string $field): ?string {
    if (empty($errors[$field]) || !is_array($errors[$field])) {
        return null;
    }
    foreach ($errors[$field] as $message) {
        if (is_string($message) && $message !== '') {
            return $message;
        }
    }
    return null;
};
$fieldError = static function ($entity, string $field): string {
    $errors = $entity->getError($field);
    return is_array($errors) ? (string)reset($errors) : '';
};
$studentNameError   = $fieldError($student, 'student_name');
$studentStatusError = $fieldError($student, 'student_status');
$dateOfBirthError   = $fieldError($student, 'date_of_birth');
$notesError         = $fieldError($student, 'medical_notes');
$declaredAgeError   = $fieldError($student, 'declared_age');

$usernameError = $portalError($portalAccountErrors, 'username');
$emailError    = $portalError($portalAccountErrors, 'email');
$passwordError = $portalError($portalAccountErrors, 'password_hash');

$serverErrors = [];
if ($usernameError)       $serverErrors['username']      = ['Username',           $usernameError];
if ($emailError)          $serverErrors['email']         = ['Login Email',        $emailError];
if ($passwordError)       $serverErrors['password']      = ['Temporary Password', $passwordError];
if ($studentNameError !== '') $serverErrors['student-name']  = ['Student Name',   $studentNameError];
if ($dateOfBirthError !== '') $serverErrors['date-of-birth'] = ['Date of Birth',  $dateOfBirthError];
if ($declaredAgeError !== '') $serverErrors['date-of-birth'] = ['Date of Birth',  'Could not calculate age from the date of birth provided.'];
if ($studentStatusError !== '') $serverErrors['student-status'] = ['Status',      $studentStatusError];
if ($notesError !== '')   $serverErrors['medical-notes'] = ['Notes',              $notesError];
?>

<a href="#" onclick="history.back(); return false;" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Add Student</h2>
    </div>

    <p style="margin: 0 0 24px; color: var(--admin-text-secondary);">
        By default this form creates both the student profile and the student portal login. If you only need a profile record for now, you can turn the login creation off.
    </p>

    <?php if (!empty($serverErrors)): ?>
        <div class="alert alert-danger" id="server-error-summary" role="alert" style="border-radius:8px; margin-bottom:24px;">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($serverErrors as $fieldId => [$label, $error]): ?>
                    <li><a href="#<?= h($fieldId) ?>" style="color:inherit;"><?= h($label) ?>: <?= h($error) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div id="js-error-summary" class="alert alert-danger" role="alert" style="display:none; border-radius:8px; margin-bottom:24px;"></div>

    <?= $this->Form->create($student, ['class' => 'student-profile-form', 'novalidate' => true]) ?>

        <div class="admin-form-card mb-4" style="max-width: 100%; padding: 20px; background-color: var(--admin-search-bg); border: 1px solid var(--admin-card-border);">
            <div class="form-check form-switch" style="margin: 0;">
                <?= $this->Form->checkbox('create_portal_account', [
                    'id' => 'create-portal-account',
                    'checked' => $createPortalAccount,
                    'class' => 'form-check-input',
                ]) ?>
                <label for="create-portal-account" class="form-check-label" style="font-weight: 600; color: var(--admin-text-primary);">
                    Create portal login now
                </label>
            </div>
            <p style="margin: 12px 0 0; color: var(--admin-text-secondary); font-size: 14px;">
                Students with a linked login stay in <strong>adult verification pending</strong> until an admin confirms they are 18 or older &mdash; or verification is granted automatically if they are 18 or over. Profile-only students will show as <strong>No account</strong>.
            </p>
        </div>

        <div id="portal-account-section">
            <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Portal Account</h3>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="admin-form-group mb-0">
                        <label for="username" class="admin-form-label">Username <span aria-hidden="true" style="color:#c0392b;">*</span></label>
                        <?= $this->Form->text('username', [
                            'id' => 'username',
                            'required' => true,
                            'maxlength' => 50,
                            'pattern' => '[A-Za-z0-9_.-]+',
                            'placeholder' => 'e.g. jsmith',
                            'value' => $portalAccount['username'],
                            'class' => 'admin-form-input' . ($usernameError ? ' is-invalid' : ''),
                            'aria-describedby' => $usernameError ? 'username-error' : null,
                        ]) ?>
                        <?php if ($usernameError): ?>
                            <div class="invalid-feedback d-block" id="username-error"><?= h($usernameError) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-form-group mb-0">
                        <label for="email" class="admin-form-label">Login Email <span aria-hidden="true" style="color:#c0392b;">*</span></label>
                        <?= $this->Form->email('email', [
                            'id' => 'email',
                            'required' => true,
                            'placeholder' => 'e.g. student@example.com',
                            'value' => $portalAccount['email'],
                            'class' => 'admin-form-input' . ($emailError ? ' is-invalid' : ''),
                            'aria-describedby' => $emailError ? 'email-error' : null,
                        ]) ?>
                        <?php if ($emailError): ?>
                            <div class="invalid-feedback d-block" id="email-error"><?= h($emailError) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-form-group mb-0">
                        <label for="password" class="admin-form-label">Temporary Password <span aria-hidden="true" style="color:#c0392b;">*</span></label>
                        <?= $this->Form->password('password', [
                            'id' => 'password',
                            'required' => true,
                            'minlength' => 8,
                            'placeholder' => 'Minimum 8 characters',
                            'value' => $portalAccount['password'],
                            'class' => 'admin-form-input' . ($passwordError ? ' is-invalid' : ''),
                            'aria-describedby' => $passwordError ? 'password-error' : null,
                        ]) ?>
                        <?php if ($passwordError): ?>
                            <div class="invalid-feedback d-block" id="password-error"><?= h($passwordError) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--admin-card-border); margin: 32px 0;">
        </div>

        <div id="profile-only-note" class="alert alert-secondary mb-4" style="display: none;">
            This will create a student profile only. They will not be able to log in until a portal account is created later.
        </div>

        <div class="row g-4">
            <div class="col-md-12">
                <div class="admin-form-group mb-0">
                    <label for="student-name" class="admin-form-label">Student Name <span aria-hidden="true" style="color:#c0392b;">*</span></label>
                    <?= $this->Form->text('student_name', [
                        'id' => 'student-name',
                        'required' => true,
                        'maxlength' => 100,
                        'placeholder' => 'e.g. Jane Smith',
                        'class' => 'admin-form-input' . ($studentNameError !== '' ? ' is-invalid' : ''),
                        'aria-describedby' => $studentNameError !== '' ? 'student-name-error' : null,
                    ]) ?>
                    <?php if ($studentNameError !== ''): ?>
                        <div class="invalid-feedback d-block" id="student-name-error"><?= h($studentNameError) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth <span aria-hidden="true" style="color:#c0392b;">*</span></label>
                    <?= $this->Form->date('date_of_birth', [
                        'id' => 'date-of-birth',
                        'required' => true,
                        'max' => date('Y-m-d'),
                        'class' => 'admin-form-input' . ($dateOfBirthError !== '' || $declaredAgeError !== '' ? ' is-invalid' : ''),
                        'aria-describedby' => $dateOfBirthError !== '' ? 'date-of-birth-error' : null,
                    ]) ?>
                    <p style="margin: 6px 0 0; font-size: 13px; color: var(--admin-text-secondary);">
                        Age is calculated automatically from date of birth.
                    </p>
                    <?php if ($dateOfBirthError !== '' || $declaredAgeError !== ''): ?>
                        <div class="invalid-feedback d-block" id="date-of-birth-error">
                            <?= h($dateOfBirthError !== '' ? $dateOfBirthError : 'Could not calculate age from the date provided.') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-status" class="admin-form-label">Status <span aria-hidden="true" style="color:#c0392b;">*</span></label>
                    <?= $this->Form->select('student_status', [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ], [
                        'id' => 'student-status',
                        'required' => true,
                        'default' => 'active',
                        'class' => 'admin-form-select' . ($studentStatusError !== '' ? ' is-invalid' : ''),
                    ]) ?>
                    <?php if ($studentStatusError !== ''): ?>
                        <div class="invalid-feedback d-block"><?= h($studentStatusError) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?= $this->Form->hidden('declared_age', ['id' => 'declared-age-hidden', 'value' => $student->declared_age]) ?>

        <div class="admin-form-group mt-4">
            <label for="medical-notes" class="admin-form-label">Notes</label>
            <?= $this->Form->textarea('medical_notes', [
                'id' => 'medical-notes',
                'rows' => 4,
                'maxlength' => 1000,
                'placeholder' => 'Any relevant notes about this student...',
                'class' => 'admin-form-textarea' . ($notesError !== '' ? ' is-invalid' : ''),
            ]) ?>
            <?php if ($notesError !== ''): ?>
                <div class="invalid-feedback d-block"><?= h($notesError) ?></div>
            <?php endif; ?>
        </div>

        <div class="admin-form-actions">
            <?= $this->Form->button($createPortalAccount ? 'Save Student & Create Login' : 'Save Student', ['class' => 'admin-btn-primary', 'id' => 'save-student-button']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>

    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle        = document.getElementById('create-portal-account');
    var accountSection = document.getElementById('portal-account-section');
    var profileOnlyNote = document.getElementById('profile-only-note');
    var submitButton  = document.getElementById('save-student-button');
    var form          = document.querySelector('.student-profile-form');
    var dobInput      = document.getElementById('date-of-birth');
    var ageHidden     = document.getElementById('declared-age-hidden');
    var jsSummary     = document.getElementById('js-error-summary');
    var today         = new Date('<?= date('Y-m-d') ?>T00:00:00');

    if (!toggle || !accountSection || !profileOnlyNote || !submitButton) return;

    var accountInputs = accountSection.querySelectorAll('input');

    function syncPortalAccountState() {
        var enabled = toggle.checked;
        accountSection.style.display = enabled ? '' : 'none';
        profileOnlyNote.style.display = enabled ? 'none' : '';
        submitButton.textContent = enabled ? 'Save Student & Create Login' : 'Save Student';
        accountInputs.forEach(function (input) {
            input.disabled = !enabled;
        });
    }

    syncPortalAccountState();
    toggle.addEventListener('change', syncPortalAccountState);

    function calculateAge(dateString) {
        if (!dateString) return null;
        var dob = new Date(dateString + 'T00:00:00');
        if (Number.isNaN(dob.getTime()) || dob > today) return null;
        var age = today.getFullYear() - dob.getFullYear();
        var md = today.getMonth() - dob.getMonth();
        if (md < 0 || (md === 0 && today.getDate() < dob.getDate())) age -= 1;
        return age;
    }

    function syncAge() {
        if (!dobInput || !ageHidden) return;
        var age = calculateAge(dobInput.value);
        ageHidden.value = age !== null ? String(age) : '';
    }

    if (dobInput && ageHidden) {
        dobInput.addEventListener('input', syncAge);
        dobInput.addEventListener('change', syncAge);
        syncAge();
    }

    form.setAttribute('novalidate', '');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var errors = [];

        form.querySelectorAll('input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled])').forEach(function (field) {
            field.classList.remove('is-invalid');
            if (!field.checkValidity()) {
                field.classList.add('is-invalid');
                var label = form.querySelector('label[for="' + field.id + '"]');
                var labelText = label ? label.textContent.replace('*', '').trim() : field.name;
                errors.push({ id: field.id, label: labelText, message: field.validationMessage });
            }
        });

        if (errors.length > 0) {
            var html = '<strong>Please fix the following errors:</strong><ul class="mb-0 mt-2">';
            errors.forEach(function (err) {
                html += '<li><a href="#' + err.id + '" style="color:inherit;">' + err.label + ': ' + err.message + '</a></li>';
            });
            html += '</ul>';
            jsSummary.innerHTML = html;
            jsSummary.style.display = '';
            jsSummary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            jsSummary.style.display = 'none';
            form.submit();
        }
    });

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
        field.addEventListener('input', function () {
            if (field.disabled || field.type === 'hidden') return;
            field.classList.toggle('is-invalid', !field.checkValidity());
        });
        field.addEventListener('change', function () {
            if (field.disabled || field.type === 'hidden') return;
            field.classList.toggle('is-invalid', !field.checkValidity());
        });
    });
});
</script>
