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
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Add Student</h2>
    </div>

    <p style="margin: 0 0 24px; color: var(--admin-text-secondary);">
        By default this form creates both the student profile and the student portal login. If you only need a profile record for now, you can turn the login creation off.
    </p>
    
    <?= $this->Form->create($student) ?>
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
                Students with a linked login stay in <strong>adult verification pending</strong> until an admin confirms they are 18 or older. Profile-only students will show as <strong>No account</strong>.
            </p>
        </div>

        <div id="portal-account-section">
            <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Portal Account</h3>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="admin-form-group mb-0">
                        <label for="username" class="admin-form-label">Username</label>
                        <?= $this->Form->text('username', [
                            'id' => 'username',
                            'required' => true,
                            'maxlength' => 50,
                            'pattern' => '[A-Za-z0-9_.-]+',
                            'value' => $portalAccount['username'],
                            'class' => 'admin-form-input'
                        ]) ?>
                        <?php if ($portalError($portalAccountErrors, 'username')): ?>
                            <div class="text-danger small mt-1"><?= h((string)$portalError($portalAccountErrors, 'username')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-form-group mb-0">
                        <label for="email" class="admin-form-label">Login Email</label>
                        <?= $this->Form->email('email', [
                            'id' => 'email',
                            'required' => true,
                            'value' => $portalAccount['email'],
                            'class' => 'admin-form-input'
                        ]) ?>
                        <?php if ($portalError($portalAccountErrors, 'email')): ?>
                            <div class="text-danger small mt-1"><?= h((string)$portalError($portalAccountErrors, 'email')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-form-group mb-0">
                        <label for="password" class="admin-form-label">Temporary Password</label>
                        <?= $this->Form->password('password', [
                            'id' => 'password',
                            'required' => true,
                            'minlength' => 8,
                            'value' => $portalAccount['password'],
                            'class' => 'admin-form-input'
                        ]) ?>
                        <?php if ($portalError($portalAccountErrors, 'password_hash')): ?>
                            <div class="text-danger small mt-1"><?= h((string)$portalError($portalAccountErrors, 'password_hash')) ?></div>
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
                    <label for="student-name" class="admin-form-label">Student Name</label>
                    <?= $this->Form->text('student_name', [
                        'id' => 'student-name', 
                        'required' => true, 
                        'maxlength' => 100,
                        'class' => 'admin-form-input'
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
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('student_status', [
                        'active' => 'Active', 
                        'inactive' => 'Inactive'
                    ], [
                        'id' => 'student-status', 
                        'default' => 'active',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth (Optional)</label>
                    <?= $this->Form->date('date_of_birth', [
                        'id' => 'date-of-birth',
                        'required' => false,
                        'empty' => true,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="medical-notes" class="admin-form-label">Medical Notes</label>
            <?= $this->Form->textarea('medical_notes', [
                'id' => 'medical-notes', 
                'rows' => 4, 
                'maxlength' => 1000,
                'class' => 'admin-form-textarea'
            ]) ?>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button($createPortalAccount ? 'Save Student & Create Login' : 'Save Student', ['class' => 'admin-btn-primary', 'id' => 'save-student-button']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('create-portal-account');
    var accountSection = document.getElementById('portal-account-section');
    var profileOnlyNote = document.getElementById('profile-only-note');
    var submitButton = document.getElementById('save-student-button');

    if (!toggle || !accountSection || !profileOnlyNote || !submitButton) {
        return;
    }

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
});
</script>
