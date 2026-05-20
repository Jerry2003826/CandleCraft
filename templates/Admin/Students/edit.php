<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Edit Customer');
$fieldError = static function ($entity, string $field): string {
    $errors = $entity->getError($field);
    return is_array($errors) ? (string)reset($errors) : '';
};
$studentNameError   = $fieldError($student, 'student_name');
$studentStatusError = $fieldError($student, 'student_status');
$dateOfBirthError   = $fieldError($student, 'date_of_birth');
$declaredAgeError   = $fieldError($student, 'declared_age');
$notesError         = $fieldError($student, 'medical_notes');

$linkedUser = null;
if ($student->user_id) {
    $usersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Users');
    $linkedUser = $usersTable->find()->where(['user_id' => $student->user_id])->first();
}

$serverErrors = [];
if ($studentNameError !== '')  $serverErrors['student-name']  = ['Student Name',    $studentNameError];
if ($dateOfBirthError !== '' || $declaredAgeError !== '') {
    $serverErrors['date-of-birth'] = ['Date of Birth', $dateOfBirthError !== '' ? $dateOfBirthError : 'Could not calculate age from the date provided.'];
}
if ($studentStatusError !== '') $serverErrors['student-status'] = ['Status',        $studentStatusError];
if ($notesError !== '')         $serverErrors['medical-notes']  = ['Notes',         $notesError];
?>

<a href="#" onclick="history.back(); return false;" class="admin-back-link" style="display: inline-block; margin-bottom: 28px;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit Customer: <?= h($student->student_name) ?></h2>
    </div>

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
                    ]) ?>
                    <?php if ($studentNameError !== ''): ?>
                        <div class="invalid-feedback d-block" id="student-name-error"><?= h($studentNameError) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth</label>
                    <?= $this->Form->date('date_of_birth', [
                        'id' => 'date-of-birth',
                        'required' => false,
                        'max' => date('Y-m-d'),
                        'class' => 'admin-form-input' . ($dateOfBirthError !== '' || $declaredAgeError !== '' ? ' is-invalid' : ''),
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

        <?php if ($linkedUser): ?>
            <div class="admin-form-group mt-4 p-4" style="background-color: var(--admin-search-bg); border-radius: 12px;">
                <h3 class="admin-form-label" style="font-weight: 600; margin-bottom: 12px;">Account &amp; Age Verification</h3>
                <div style="margin-bottom: 8px;">
                    <span style="color: var(--admin-text-secondary); font-size: 14px;">Account Status:</span>
                    <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= h($linkedUser->account_status) ?></strong>
                </div>
                <div style="margin-bottom: 8px;">
                    <span style="color: var(--admin-text-secondary); font-size: 14px;">Admin verified 18+:</span>
                    <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= $linkedUser->age_verified_by_admin ? 'Yes' : 'No' ?></strong>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a href="<?= $this->Url->build(['action' => 'view', $student->student_id]) ?>"
                       style="display:inline-flex; align-items:center; padding:8px 18px; border-radius:8px; border:none; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; background-color:#FEF3C7; color:#92400E;">
                        <i class="bi bi-shield-exclamation me-2"></i> Open Customer Details
                    </a>
                    <?php if ($linkedUser->age_verified_by_admin): ?>
                        <button
                            type="submit"
                            form="remove-adult-verification-form"
                            style="display:inline-flex; align-items:center; padding:8px 18px; border-radius:8px; border:none; font-size:14px; font-weight:600; cursor:pointer; background-color:#EF4444; color:#fff;"
                            onclick="return confirm('Are you sure you want to remove adult verification and lock booking/payment access again?');"
                        >
                            <i class="bi bi-shield-x me-2"></i> Remove Verification
                        </button>
                    <?php else: ?>
                        <p style="margin: 0; align-self: center; color: var(--admin-text-secondary); font-size: 13px;">
                            Open the customer details page when you are ready to verify adult status.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-form-actions">
            <?= $this->Form->button('Update Customer', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'view', $student->student_id]) ?>" class="admin-btn-secondary">Cancel</a>
        </div>

    <?= $this->Form->end() ?>

    <?php if ($linkedUser && $linkedUser->age_verified_by_admin): ?>
        <?= $this->Form->create(null, [
            'url' => ['action' => 'unverifyAge', $student->student_id],
            'id' => 'remove-adult-verification-form',
            'style' => 'display: none;',
        ]) ?>
        <?= $this->Form->end() ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form      = document.querySelector('.student-profile-form');
    var dobInput  = document.getElementById('date-of-birth');
    var ageHidden = document.getElementById('declared-age-hidden');
    var jsSummary = document.getElementById('js-error-summary');
    var today     = new Date('<?= date('Y-m-d') ?>T00:00:00');

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

    if (!form) return;

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
