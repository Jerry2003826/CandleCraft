<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', 'Edit My Account');
$emailErrors = $user->getError('email');
$emailError = is_array($emailErrors) ? (string)reset($emailErrors) : '';
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to My Account
</a>

<div class="admin-form-card" style="max-width: 100%;">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit My Account</h2>
    </div>

    <?= $this->Form->create($student) ?>
        <div class="admin-form-group" style="padding: 16px 18px; border-radius: 12px; background-color: var(--admin-search-bg); border: 1px solid var(--admin-card-border);">
            <div class="admin-form-label" style="margin-bottom: 6px;">Adult verification notice</div>
            <p style="margin: 0; font-size: 14px; color: var(--admin-text-secondary); line-height: 1.6;">
                If your updated age details show you are under 18, any existing adult verification will be removed automatically and booking/payment access will lock again.
            </p>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="account-username" class="admin-form-label">Username</label>
                    <input
                        id="account-username"
                        type="text"
                        class="admin-form-input"
                        value="<?= h((string)$user->username) ?>"
                        readonly
                    >
                    <p style="margin: 8px 0 0; font-size: 13px; color: var(--admin-text-secondary);">
                        Username is shown for reference and stays unchanged here.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="email" class="admin-form-label">Email</label>
                    <?= $this->Form->email('email', [
                        'id' => 'email',
                        'required' => true,
                        'value' => (string)$user->email,
                        'class' => 'admin-form-input' . ($emailError !== '' ? ' is-invalid' : ''),
                    ]) ?>
                    <?php if ($emailError !== ''): ?>
                        <div class="invalid-feedback d-block"><?= h($emailError) ?></div>
                    <?php endif; ?>
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
                        'class' => 'admin-form-input',
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="declared-age" class="admin-form-label">Declared Age</label>
                    <?= $this->Form->number('declared_age', [
                        'id' => 'declared-age',
                        'required' => true,
                        'min' => 1,
                        'max' => 120,
                        'class' => 'admin-form-input',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth</label>
                    <?= $this->Form->text('date_of_birth', [
                        'type' => 'date',
                        'id' => 'date-of-birth',
                        'required' => false,
                        'value' => $student->date_of_birth?->format('Y-m-d') ?? '',
                        'class' => 'admin-form-input',
                    ]) ?>
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
                'class' => 'admin-form-textarea',
            ]) ?>
        </div>

        <div class="admin-form-actions">
            <?= $this->Form->button('Save Changes', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
