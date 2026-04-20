<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Student Details');
$messageId = $this->request->getQuery('message');
$calculatedAge = null;

if ($student->date_of_birth) {
    $calculatedAge = (int)$student->date_of_birth->diff(new \Cake\Chronos\ChronosDate())->y;
}

$effectiveAge = $calculatedAge ?? ($student->declared_age !== null ? (int)$student->declared_age : null);
$canVerifyAdult = $effectiveAge !== null && $effectiveAge >= 18;
$ageEvidenceLabel = $student->date_of_birth ? 'date of birth' : 'declared age';
$verificationBlockedMessage = $effectiveAge === null
    ? 'Admin verification is only available once a date of birth or declared age has been recorded for this customer.'
    : 'Admin verification is only available when the recorded age is 18 or older. The current ' . $ageEvidenceLabel . ' indicates this customer is not eligible yet.';
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link mb-0">
            <i class="bi bi-arrow-left"></i> Back to Students
        </a>
        <?php if ($messageId): ?>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'view', $messageId]) ?>" class="admin-back-link mb-0">
                <i class="bi bi-envelope"></i> Back to Request
            </a>
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>" class="admin-btn-secondary" style="color: #D97706; padding: 6px 16px; font-size: 13px;">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?= $this->Form->postLink(
            '<i class="bi bi-trash me-1"></i> Delete',
            ['action' => 'delete', $student->student_id],
            [
                'confirm' => __('Are you sure you want to delete {0}?', $student->student_name),
                'class' => 'admin-btn-secondary',
                'style' => 'color: #EF4444; padding: 6px 16px; font-size: 13px;',
                'escape' => false
            ]
        ) ?>
    </div>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
        <h2 class="admin-form-title" style="font-size: 24px; margin: 0;"><?= h($student->student_name) ?></h2>
        <?php 
            $statusClass = 'admin-badge-neutral';
            if ($student->student_status === 'active') $statusClass = 'admin-badge-success';
            if ($student->student_status === 'inactive') $statusClass = 'admin-badge-danger';
        ?>
        <span class="admin-badge <?= $statusClass ?>" style="padding: 6px 12px; font-size: 13px;"><?= h(ucfirst($student->student_status)) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Name</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= h($student->student_name) ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Declared Age</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= $student->declared_age !== null ? h((string)$student->declared_age) : '-' ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Date of Birth</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Medical Notes</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 400; font-size: 14px; color: var(--admin-text-primary); line-height: 1.5;">
                    <?= $student->medical_notes ? nl2br(h($student->medical_notes)) : '<span class="text-muted">N/A</span>' ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Created At</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= $student->created_at ? $student->created_at->format('j M Y, g:ia') : '-' ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Updated At</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= $student->updated_at ? $student->updated_at->format('j M Y, g:ia') : '-' ?>
                </div>
            </div>
            
            <?php if ($student->user): ?>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Account Status</div>
                    <?php 
                        $accStatusClass = 'admin-badge-neutral';
                        if ($student->user->account_status === 'active') $accStatusClass = 'admin-badge-success';
                        if ($student->user->account_status === 'inactive') $accStatusClass = 'admin-badge-danger';
                    ?>
                    <span class="admin-badge <?= $accStatusClass ?>" style="padding: 4px 10px; font-size: 12px;"><?= h(ucfirst($student->user->account_status)) ?></span>
                </div>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Adult Verified (Admin)</div>
                    <?php if ($student->user->age_verified_by_admin): ?>
                        <span class="admin-badge admin-badge-success" style="padding: 4px 10px; font-size: 12px;"><i class="bi bi-shield-check me-1"></i>Verified</span>
                    <?php else: ?>
                        <span class="admin-badge admin-badge-warning" style="padding: 4px 10px; font-size: 12px;"><i class="bi bi-exclamation-triangle me-1"></i>Not Verified</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$student->user): ?>
    <div class="admin-form-card mb-4" style="max-width: 100%; padding: 28px; border: 1px solid rgba(59, 130, 246, 0.25); background-color: rgba(59, 130, 246, 0.05);">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: rgba(59, 130, 246, 0.12); display: flex; justify-content: center; align-items: center;">
                <i class="bi bi-person-lock" style="font-size: 20px; color: #2563EB;"></i>
            </div>
            <h3 class="admin-form-title m-0" style="color: #1D4ED8; font-size: 18px;">No Portal Login Linked Yet</h3>
        </div>

        <p style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary); line-height: 1.6; margin: 0;">
            Adult verification is only needed after a student portal login exists. This profile can be kept for roster/admin use, but the student cannot sign in until a linked account is created.
        </p>
    </div>
<?php endif; ?>

<?php if ($student->user && !$student->user->age_verified_by_admin): ?>
    <div class="admin-form-card" style="max-width: 100%; padding: 32px; border: 1px solid rgba(245, 158, 11, 0.3); background-color: rgba(245, 158, 11, 0.05);">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: rgba(245, 158, 11, 0.1); display: flex; justify-content: center; align-items: center;">
                <i class="bi bi-shield-exclamation" style="font-size: 20px; color: #F59E0B;"></i>
            </div>
            <h3 class="admin-form-title m-0" style="color: #D97706; font-size: 18px;">Adult Verification Required</h3>
        </div>
        
        <p style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary); line-height: 1.6; margin-bottom: 16px;">
            This student can only browse courses until an administrator confirms they are <strong>18 years or older</strong>.
        </p>
        
        <div style="background-color: var(--admin-card-bg); border-radius: 8px; border: 1px solid var(--admin-card-border); padding: 16px; margin-bottom: 24px;">
            <?php if ($student->declared_age !== null): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Declared age on file:</span>
                    <strong style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary);"><?= h((string)$student->declared_age) ?></strong>
                </div>
            <?php endif; ?>
            <?php if ($student->date_of_birth): ?>
                <?php
                $dob = $student->date_of_birth;
                $age = $calculatedAge;
                ?>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Date of birth:</span>
                    <strong style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary);"><?= $dob->format('j M Y') ?></strong>
                    <span style="color: var(--admin-text-secondary); margin: 0 8px;">&mdash;</span>
                    <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Calculated age:</span>
                    <strong style="font-family: 'Inter', sans-serif; font-size: 15px; color: <?= $age >= 18 ? '#10B981' : '#EF4444' ?>;"><?= $age ?> years old</strong>
                </div>
            <?php endif; ?>
        </div>

        <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); line-height: 1.5; margin-bottom: 24px;">
            By clicking the button below, you confirm that you have verified this student is 18 years or older. This will <strong>enable booking and payment features</strong> for this user.
        </p>

        <?php if ($canVerifyAdult): ?>
            <?= $this->Form->postLink(
                '<i class="bi bi-shield-check me-2"></i> Verify Adult Status & Enable Access',
                ['action' => 'verifyAge', $student->student_id],
                [
                    'escape' => false,
                    'class' => 'admin-btn-primary',
                    'style' => 'background-color: #10B981; color: #FFFFFF; font-size: 15px; padding: 12px 24px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);',
                    'confirm' => 'Are you sure you want to verify this student is 18+ and enable their booking and payment access?',
                ]
            ) ?>
        <?php else: ?>
            <div style="border-radius: 10px; border: 1px solid rgba(239, 68, 68, 0.28); background: rgba(239, 68, 68, 0.08); padding: 16px 18px; color: var(--admin-text-primary);">
                <strong style="display: block; margin-bottom: 8px; color: #DC2626;">Verification blocked</strong>
                <span style="font-family: 'Inter', sans-serif; font-size: 14px; line-height: 1.5;">
                    <?= h($verificationBlockedMessage) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($student->user && $student->user->age_verified_by_admin): ?>
    <div class="admin-form-card" style="max-width: 100%; padding: 32px; border: 1px solid rgba(16, 185, 129, 0.28); background-color: rgba(16, 185, 129, 0.06);">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: rgba(16, 185, 129, 0.12); display: flex; justify-content: center; align-items: center;">
                <i class="bi bi-shield-check" style="font-size: 20px; color: #10B981;"></i>
            </div>
            <h3 class="admin-form-title m-0" style="color: #047857; font-size: 18px;">Adult Verification Active</h3>
        </div>

        <p style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary); line-height: 1.6; margin-bottom: 20px;">
            Booking and payment are currently unlocked for this customer. Use the action below if the verification was recorded by mistake or needs to be removed.
        </p>

        <?= $this->Form->postLink(
            '<i class="bi bi-shield-x me-2"></i> Remove Adult Verification',
            ['action' => 'unverifyAge', $student->student_id],
            [
                'escape' => false,
                'class' => 'admin-btn-secondary',
                'style' => 'color: #B45309; border-color: rgba(180, 83, 9, 0.22); background-color: rgba(255, 255, 255, 0.7); font-size: 15px; padding: 12px 24px;',
                'confirm' => 'Are you sure you want to remove adult verification and lock booking/payment access again?',
            ]
        ) ?>
    </div>
<?php endif; ?>
