<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var int $availableSlots
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Book a Class');
$courseType = strtolower($class->course?->course_type ?? 'default');
$typeColor = $courseType === 'pottery' ? '#1D4ED8' : ($courseType === 'knitting' ? '#B45309' : '#374151');
$typeBg = $courseType === 'pottery' ? '#DBEAFE' : ($courseType === 'knitting' ? '#FEF3C7' : '#F3F4F6');
?>

<a href="#" onclick="history.back(); return false;" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Review Booking: <?= h($class->class_code) ?></h2>
    </div>
    
    <!-- Class Info Card -->
    <div style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 24px; margin-bottom: 32px;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 pb-4" style="border-bottom: 1px solid var(--admin-card-border);">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="background-color: <?= $typeBg ?>; color: <?= $typeColor ?>; padding: 4px 10px; border-radius: 12px; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <?= h($class->course?->course_type ?? 'Course') ?>
                    </span>
                    <span style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                        <?= h(ucfirst(str_replace('_', ' ', $class->course?->course_level ?? 'All Levels'))) ?>
                    </span>
                </div>
                
                <h3 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 24px; color: var(--admin-text-primary); margin: 0 0 12px 0;">
                    <?= h($class->course?->course_name ?? 'Class') ?>
                </h3>
                
                <div class="d-flex flex-wrap gap-4" style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock"></i>
                        <?= $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : '-' ?> - <?= $class->end_datetime ? $class->end_datetime->format('g:ia') : '-' ?>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt"></i><?= h($class->location) ?>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-person"></i><?= h($class->teacher?->teacher_name ?? 'TBA') ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--admin-text-secondary); display: block; margin-bottom: 4px;">
                    Availability
                </span>
                <strong style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 18px; color: var(--admin-text-primary);">
                    <?= h((string)$availableSlots) ?> / <?= h((string)$class->capacity) ?> spots
                </strong>
            </div>
            <div class="col-6">
                <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--admin-text-secondary); display: block; margin-bottom: 4px;">
                    Price
                </span>
                <strong style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 18px; color: var(--admin-text-primary);">
                    $<?= number_format((float)($class->course?->course_price ?? 0), 2) ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <?= $this->Form->create(null, ['url' => ['action' => 'add', $class->class_id]]) ?>
        <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 16px; color: var(--admin-brand-icon);">Proceed to Payment</h3>
        <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 24px;">
            You are booking as <strong style="color: var(--admin-text-primary);"><?= h($student->student_name ?? '') ?></strong>. The class will only be confirmed after successful payment.
        </p>
        <div class="admin-form-actions mt-4">
            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-credit-card"></i> Proceed to Payment
            </button>
            <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
