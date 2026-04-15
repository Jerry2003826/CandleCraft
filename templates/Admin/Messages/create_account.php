<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 * @var array<string, mixed> $requestMeta
 * @var \App\Model\Entity\User|null $existingUser
 * @var \App\Model\Entity\Student|null $linkedStudent
 * @var array<string, mixed> $account
 * @var array<string, string> $accountStatusOptions
 * @var array<string, string> $profileStatusOptions
 */
$this->assign('title', 'Create Account');
?>

<a href="<?= $this->Url->build(['action' => 'view', $message->message_id]) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Request
</a>

<?php if ($existingUser): ?>
    <div class="alert alert-success">
        An account already exists for <strong><?= h($existingUser->email) ?></strong> as <strong><?= h($existingUser->username) ?></strong>.
        <?php if ($linkedStudent): ?>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Students', 'action' => 'view', $linkedStudent->student_id, '?' => ['message' => $message->message_id]]) ?>" class="ms-2">Open student record</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="admin-form-card mb-4">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Request Summary</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <p class="mb-1" style="color: var(--admin-text-secondary); font-size: 14px;">Requester</p>
            <p class="mb-0" style="color: var(--admin-text-primary); font-weight: 600;"><?= h($message->sender_name ?: '-') ?></p>
        </div>
        <div class="col-md-6">
            <p class="mb-1" style="color: var(--admin-text-secondary); font-size: 14px;">Requested Account</p>
            <p class="mb-0" style="color: var(--admin-text-primary); font-weight: 600;">Student</p>
        </div>
        <div class="col-md-6">
            <p class="mb-1" style="color: var(--admin-text-secondary); font-size: 14px;">Email</p>
            <p class="mb-0" style="color: var(--admin-text-primary); font-weight: 600;"><?= h($message->sender_email ?: '-') ?></p>
        </div>
        <div class="col-md-6">
            <p class="mb-1" style="color: var(--admin-text-secondary); font-size: 14px;">Phone</p>
            <p class="mb-0" style="color: var(--admin-text-primary); font-weight: 600;"><?= h($message->sender_phone ?: '-') ?></p>
        </div>
        <div class="col-md-6">
            <p class="mb-1" style="color: var(--admin-text-secondary); font-size: 14px;">Declared Age</p>
            <p class="mb-0" style="color: var(--admin-text-primary); font-weight: 600;"><?= $requestMeta['declared_age'] !== null ? h((string)$requestMeta['declared_age']) : '-' ?></p>
        </div>
        <div class="col-12">
            <p class="mb-1" style="color: var(--admin-text-secondary); font-size: 14px;">Notes</p>
            <p class="mb-0" style="color: var(--admin-text-primary);"><?= nl2br(h((string)($requestMeta['clean_message_text'] ?: 'No additional notes provided.'))) ?></p>
        </div>
    </div>
</div>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Create Portal Account</h2>
    </div>
    <p style="margin: 0 0 24px; color: var(--admin-text-secondary);">
        Saving this form will create the student login and immediately email the requester their login address and temporary password.
    </p>

    <?= $this->Form->create(null) ?>
        <?= $this->Form->hidden('user_role', ['value' => 'student']) ?>
        <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Account Information</h3>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="admin-form-group mb-0">
                    <label for="username" class="admin-form-label">Username</label>
                    <?= $this->Form->text('username', [
                        'id' => 'username',
                        'required' => true,
                        'maxlength' => 50,
                        'pattern' => '[A-Za-z0-9_.-]+',
                        'value' => $account['username'],
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-form-group mb-0">
                    <label for="account-status" class="admin-form-label">Account Status</label>
                    <?= $this->Form->select('account_status', $accountStatusOptions, [
                        'id' => 'account-status',
                        'value' => $account['account_status'],
                        'class' => 'admin-form-select',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="email" class="admin-form-label">Email</label>
                    <?= $this->Form->email('email', [
                        'id' => 'email',
                        'required' => true,
                        'value' => $account['email'],
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="password" class="admin-form-label">Temporary Password</label>
                    <?= $this->Form->password('password', [
                        'id' => 'password',
                        'required' => true,
                        'minlength' => 8,
                        'value' => $account['password'],
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
        </div>

        <hr style="border-color: var(--admin-card-border); margin: 32px 0;">

        <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Profile Information</h3>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="profile-name" class="admin-form-label">Student Name</label>
                    <?= $this->Form->text('profile_name', [
                        'id' => 'profile-name',
                        'required' => true,
                        'maxlength' => 100,
                        'value' => $account['profile_name'],
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="phone-number" class="admin-form-label">Phone Number</label>
                    <?= $this->Form->text('phone_number', [
                        'id' => 'phone-number',
                        'maxlength' => 30,
                        'pattern' => '\+?[0-9\s()-]{8,30}',
                        'value' => $account['phone_number'],
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="declared-age" class="admin-form-label">Declared Age</label>
                    <?= $this->Form->number('declared_age', [
                        'id' => 'declared-age',
                        'value' => $account['declared_age'],
                        'required' => true,
                        'min' => 1,
                        'max' => 120,
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth (Optional)</label>
                    <?= $this->Form->date('date_of_birth', [
                        'id' => 'date-of-birth',
                        'value' => $account['date_of_birth'],
                        'class' => 'admin-form-input',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-status" class="admin-form-label">Student Status</label>
                    <?= $this->Form->select('student_status', $profileStatusOptions, [
                        'id' => 'student-status',
                        'value' => $account['student_status'],
                        'class' => 'admin-form-select',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
            <div class="col-12">
                <div class="admin-form-group mb-0">
                    <label class="admin-form-label">Adult Verification Rule</label>
                    <div style="padding: 16px 18px; background-color: var(--admin-search-bg); border-radius: 12px;">
                        Students created from requests can browse courses immediately, but they cannot book or pay until an administrator confirms they are 18 or older from the student record.
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="admin-form-group mb-0">
                    <label for="medical-notes" class="admin-form-label">Medical Notes</label>
                    <?= $this->Form->textarea('medical_notes', [
                        'id' => 'medical-notes',
                        'rows' => 4,
                        'maxlength' => 1000,
                        'value' => $account['medical_notes'],
                        'class' => 'admin-form-textarea',
                        'disabled' => (bool)$existingUser,
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="admin-form-actions">
            <?= $this->Form->button('Create Account & Send Email', ['class' => 'admin-btn-primary', 'disabled' => (bool)$existingUser]) ?>
            <a href="<?= $this->Url->build(['action' => 'view', $message->message_id]) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
