<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 * @var bool $stripeReady
 * @var bool $demoModeEnabled
 */
$this->assign('title', 'Payment');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> My Schedule</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Complete Payment</h5>
    </div>
    <div class="card-body">
        <div class="portal-class-card mb-4">
            <div class="portal-class-card__header">
                <div>
                    <p class="portal-class-card__eyebrow"><?= h($booking->student?->student_name ?? 'Student') ?></p>
                    <h3><?= h($booking->class_entity?->course?->course_name ?? 'Class Booking') ?></h3>
                </div>
            </div>
            <div class="portal-class-card__meta">
                <span><i class="bi bi-tag me-1"></i><?= h($booking->class_entity?->class_code ?? '-') ?></span>
                <span><i class="bi bi-clock me-1"></i><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('D j M Y, g:ia') : '-' ?></span>
                <span><i class="bi bi-geo-alt me-1"></i><?= h($booking->class_entity?->location ?? '-') ?></span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="card bg-light border-0 p-3">
                    <div class="stat-label">Student</div>
                    <strong class="mt-1"><?= h($booking->student?->student_name ?? '-') ?></strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card bg-light border-0 p-3">
                    <div class="stat-label">Amount</div>
                    <strong class="mt-1" style="font-size: 1.4em;">$<?= number_format((float)$booking->price_at_booking, 2) ?> AUD</strong>
                </div>
            </div>
        </div>

        <?php if (!empty($stripeReady)): ?>
            <p class="text-muted mb-3">You will be redirected to Stripe's secure payment page.</p>
            <?= $this->Form->create(null, ['url' => ['action' => 'process', $booking->booking_id]]) ?>
            <?= $this->Form->button('<i class="bi bi-lock me-2"></i>Proceed to Secure Payment', ['class' => 'btn btn-primary w-100 py-2', 'escape' => false]) ?>
            <?= $this->Form->end() ?>
        <?php elseif (!empty($demoModeEnabled)): ?>
            <div class="alert alert-warning d-flex align-items-center">
                <i class="bi bi-info-circle me-2"></i>
                <div><strong>Demo Mode</strong> — Stripe is not configured for this environment, so the payment form is using an explicit local demo flow.</div>
            </div>
            <?= $this->Form->create(null, ['url' => ['action' => 'process', $booking->booking_id]]) ?>
            <?= $this->Form->button('<i class="bi bi-check-circle me-2"></i>Complete Demo Payment', ['class' => 'btn btn-primary w-100 py-2', 'escape' => false]) ?>
            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <div>Online payments are temporarily unavailable. Please try again later or contact the studio for help.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
