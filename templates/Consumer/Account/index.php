<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 * @var int|null $effectiveAge
 */
$this->assign('title', 'My Account');
$user = $student->user;
$verificationLabel = $user && $user->age_verified_by_admin ? 'Verified 18+' : 'Pending adult verification';
$verificationClass = $user && $user->age_verified_by_admin ? 'admin-badge-success' : 'admin-badge-warning';
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <p style="margin: 0; color: var(--admin-text-secondary); font-size: 14px;">
        Review your login details, recorded age information, and personal profile notes.
    </p>
    <a href="<?= $this->Url->build(['action' => 'edit']) ?>" class="admin-btn-primary">
        <i class="bi bi-pencil-square me-2"></i>Edit Personal Details
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-5 d-flex">
        <div class="admin-form-card flex-grow-1" style="max-width: 100%; padding: 28px;">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
                <h3 class="admin-form-title m-0" style="font-size: 20px;">Account Details</h3>
                <span class="admin-badge <?= $verificationClass ?>"><?= h($verificationLabel) ?></span>
            </div>

            <div style="display: grid; gap: 18px;">
                <div>
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Username</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);"><?= h((string)($user?->username ?? '-')) ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Email</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);"><?= h((string)($user?->email ?? '-')) ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Account Status</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);"><?= h(ucfirst((string)($user?->account_status ?? 'unknown'))) ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Portal Access</div>
                    <div style="font-size: 14px; color: var(--admin-text-primary); line-height: 1.6;">
                        <?php if ($user && $user->age_verified_by_admin): ?>
                            Booking and payment are unlocked.
                        <?php else: ?>
                            Booking and payment stay locked until an administrator confirms you are 18 or older.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7 d-flex">
        <div class="admin-form-card flex-grow-1" style="max-width: 100%; padding: 28px;">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
                <h3 class="admin-form-title m-0" style="font-size: 20px;">Personal Details</h3>
                <span class="admin-badge admin-badge-neutral"><?= $effectiveAge !== null ? 'Recorded age ' . h((string)$effectiveAge) : 'Age pending' ?></span>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Full Name</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);"><?= h((string)$student->student_name) ?></div>
                </div>
                <div class="col-md-6">
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Declared Age</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);"><?= $student->declared_age !== null ? h((string)$student->declared_age) : '-' ?></div>
                </div>
                <div class="col-md-6">
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Date of Birth</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);">
                        <?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Profile Status</div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);"><?= h(ucfirst((string)$student->student_status)) ?></div>
                </div>
                <div class="col-12">
                    <div style="font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Medical Notes</div>
                    <div style="font-size: 14px; color: var(--admin-text-primary); line-height: 1.6;">
                        <?= $student->medical_notes ? nl2br(h((string)$student->medical_notes)) : '<span style="color: var(--admin-text-secondary);">No medical notes recorded.</span>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
