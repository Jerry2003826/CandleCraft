<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', 'Edit My Account');
$emailErrors = $user->getError('email');
$emailError = is_array($emailErrors) ? (string)reset($emailErrors) : '';
$studentNameErrors = $student->getError('student_name');
$studentNameError = is_array($studentNameErrors) ? (string)reset($studentNameErrors) : '';
$dateOfBirthErrors = $student->getError('date_of_birth');
$dateOfBirthError = is_array($dateOfBirthErrors) ? (string)reset($dateOfBirthErrors) : '';
$medicalNotesErrors = $student->getError('medical_notes');
$medicalNotesError = is_array($medicalNotesErrors) ? (string)reset($medicalNotesErrors) : '';
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to My Account
</a>

<div class="admin-form-card" style="max-width: 100%;">

    <?= $this->Form->create($student, ['class' => 'account-edit-form']) ?>
        <div class="admin-form-group" style="padding: 16px 18px; border-radius: 12px; background-color: var(--admin-search-bg); border: 1px solid var(--admin-card-border);">
            <div class="admin-form-label" style="margin-bottom: 6px;">Adult verification notice</div>
            <p style="margin: 0; font-size: 14px; color: var(--admin-text-secondary); line-height: 1.6;">
                If your updated age details show you are under 18, any existing adult verification will be removed automatically and booking/payment access will lock again.
            </p>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="account-username" class="admin-form-label">
                        Username <i class="bi bi-lock-fill" style="font-size: 12px; color: var(--admin-text-secondary); vertical-align: middle;"></i>
                    </label>
                    <input
                        id="account-username"
                        type="text"
                        class="admin-form-input"
                        value="<?= h((string)$user->username) ?>"
                        readonly
                    >
                    <p style="margin: 8px 0 0; font-size: 13px; color: var(--admin-text-secondary);">
                        To change your username, please contact an administrator.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="email" class="admin-form-label">
                        Email <i class="bi bi-lock-fill" style="font-size: 12px; color: var(--admin-text-secondary); vertical-align: middle;"></i>
                    </label>
                    <input
                        id="email"
                        type="email"
                        class="admin-form-input"
                        value="<?= h((string)$user->email) ?>"
                        readonly
                    >
                    <p style="margin: 8px 0 0; font-size: 13px; color: var(--admin-text-secondary);">
                        To change your email, please contact an administrator.
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-name" class="admin-form-label">Full Name</label>
                    <?= $this->Form->text('student_name', [
                        'id' => 'student-name',
                        'required' => true,
                        'maxlength' => 100,
                        'class' => 'admin-form-input' . ($studentNameError !== '' ? ' is-invalid' : ''),
                    ]) ?>
                    <?php if ($studentNameError !== ''): ?>
                        <div class="invalid-feedback d-block"><?= h($studentNameError) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label class="admin-form-label">Age</label>
                    <?php
                    $displayAge = null;
                    if (!empty($student->date_of_birth)) {
                        $displayAge = (int)$student->date_of_birth->diff(new \Cake\Chronos\ChronosDate())->y;
                    } elseif ($student->declared_age !== null) {
                        $displayAge = (int)$student->declared_age;
                    }
                    ?>
                    <p style="margin: 0; font-size: 22px; font-weight: 600; color: var(--admin-text-primary); line-height: 1.2;">
                        <?= $displayAge !== null ? h((string)$displayAge) . ' <span style="font-size: 15px; font-weight: 400; color: var(--admin-text-secondary);">years old</span>' : '<span style="font-size: 15px; color: var(--admin-text-secondary);">Not available</span>' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">
                        Date of Birth <i class="bi bi-lock-fill" style="font-size: 12px; color: var(--admin-text-secondary); vertical-align: middle;"></i>
                    </label>
                    <?= $this->Form->text('date_of_birth', [
                        'type' => 'date',
                        'id' => 'date-of-birth',
                        'required' => false,
                        'value' => $student->date_of_birth?->format('Y-m-d') ?? '',
                        'class' => 'admin-form-input' . ($dateOfBirthError !== '' ? ' is-invalid' : ''),
                        'readonly' => true,
                    ]) ?>
                    <p style="margin: 8px 0 0; font-size: 13px; color: var(--admin-text-secondary);">
                        To change your date of birth, please contact an administrator.
                    </p>
                    <?php if ($dateOfBirthError !== ''): ?>
                        <div class="invalid-feedback d-block"><?= h($dateOfBirthError) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-status" class="admin-form-label">Profile Status</label>
                    <input
                        id="student-status"
                        type="text"
                        class="admin-form-input"
                        value="<?= h(ucfirst((string)$student->student_status)) ?>"
                        readonly
                    >
                </div>
            </div>
        </div>

        <div class="admin-form-group mt-4">
            <label for="medical-notes" class="admin-form-label">Medical Notes</label>
            <?= $this->Form->textarea('medical_notes', [
                'id' => 'medical-notes',
                'rows' => 5,
                'maxlength' => 1000,
                'class' => 'admin-form-textarea' . ($medicalNotesError !== '' ? ' is-invalid' : ''),
            ]) ?>
            <?php if ($medicalNotesError !== ''): ?>
                <div class="invalid-feedback d-block"><?= h($medicalNotesError) ?></div>
            <?php endif; ?>
        </div>

        <div class="admin-form-actions">
            <?= $this->Form->button('Save Changes', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>

