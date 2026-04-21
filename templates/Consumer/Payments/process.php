<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 * @var bool $stripeReady
 * @var bool $demoModeEnabled
 */
$this->assign('title', 'Payment');
?>

<a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> My Schedule
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Complete Payment</h2>
    </div>
    
    <div style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 24px; margin-bottom: 32px;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 pb-4" style="border-bottom: 1px solid var(--admin-card-border);">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.05em;">
                        <?= h($booking->class_entity?->class_code ?? '-') ?>
                    </span>
                </div>
                
                <h3 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 24px; color: var(--admin-text-primary); margin: 0 0 12px 0;">
                    <?= h($booking->class_entity?->course?->course_name ?? 'Class Booking') ?>
                </h3>
                
                <div class="d-flex flex-wrap gap-4" style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-tag"></i><?= h($booking->class_entity?->class_code ?? '-') ?>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock"></i>
                        <?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('D j M Y, g:ia') : '-' ?>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt"></i><?= h($booking->class_entity?->location ?? '-') ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-sm-6">
                <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--admin-text-secondary); display: block; margin-bottom: 4px;">
                    Student
                </span>
                <strong style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 18px; color: var(--admin-text-primary);">
                    <?= h($booking->student?->student_name ?? '-') ?>
                </strong>
            </div>
            <div class="col-sm-6">
                <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--admin-text-secondary); display: block; margin-bottom: 4px;">
                    Amount
                </span>
                <strong style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 24px; color: var(--admin-text-primary);">
                    $<?= number_format((float)$booking->price_at_booking, 2) ?> AUD
                </strong>
            </div>
        </div>
    </div>

    <?php if (!empty($stripeReady)): ?>
        <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 24px;">
            You will be redirected to Stripe's secure payment page.
        </p>
        <?= $this->Form->create(null, ['url' => ['action' => 'process', $booking->booking_id]]) ?>
        <button type="submit" class="admin-btn-primary w-100 justify-content-center py-3" style="font-size: 16px;">
            <i class="bi bi-lock me-2"></i> Confirm Booking &amp; Pay
        </button>
        <?= $this->Form->end() ?>
    <?php elseif (!empty($demoModeEnabled)): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" style="border-radius: 12px; border: 1px solid var(--admin-card-border);">
            <i class="bi bi-info-circle me-2" style="font-size: 20px;"></i>
            <div style="font-family: 'Inter', sans-serif; font-size: 14px;">
                <strong>Demo Mode</strong> — Stripe is not configured for this environment, so the payment form is using an explicit local demo flow.
            </div>
        </div>
        <?= $this->Form->create(null, ['url' => ['action' => 'process', $booking->booking_id]]) ?>
        <button type="submit" class="admin-btn-primary w-100 justify-content-center py-3" style="font-size: 16px;">
            <i class="bi bi-check-circle me-2"></i> Confirm Demo Payment
        </button>
        <?= $this->Form->end() ?>
    <?php else: ?>
        <div class="alert alert-warning d-flex align-items-center mb-0" style="border-radius: 12px; border: 1px solid var(--admin-card-border);">
            <i class="bi bi-exclamation-triangle me-2" style="font-size: 20px;"></i>
            <div style="font-family: 'Inter', sans-serif; font-size: 14px;">
                Online payments are temporarily unavailable. Please try again later or contact the studio for help.
            </div>
        </div>
    <?php endif; ?>
</div>
