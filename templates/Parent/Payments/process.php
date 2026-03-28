<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 * @var bool $stripeReady
 */
$this->assign('title', 'Payment');
?>

<div class="card">
    <div class="card-header">
        <h3>Complete Payment</h3>
        <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="btn btn-sm">&larr; Family Bookings</a>
    </div>
    <div class="card-body">
        <div class="portal-class-card" style="margin-bottom: 20px;">
            <div class="portal-class-card__header">
                <div>
                    <p class="portal-class-card__eyebrow">
                        <?= h($booking->student?->student_name ?? 'Student') ?>
                    </p>
                    <h3>
                        <?= h($booking->class_entity?->course?->course_name ?? 'Class Booking') ?>
                    </h3>
                </div>
            </div>
            <div class="portal-class-card__meta">
                <span><?= h($booking->class_entity?->class_code ?? '-') ?></span>
                <span><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('D j M Y, g:ia') : '-' ?></span>
                <span><?= h($booking->class_entity?->location ?? '-') ?></span>
            </div>
        </div>

        <div class="portal-status-row" style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin: 16px 0;">
            <div>
                <span class="portal-status-row__label">Student</span>
                <strong><?= h($booking->student?->student_name ?? '-') ?></strong>
            </div>
            <div>
                <span class="portal-status-row__label">Amount</span>
                <strong style="font-size: 1.4em; color: #2c3e50;">$<?= number_format((float)$booking->price_at_booking, 2) ?> AUD</strong>
            </div>
        </div>

        <?php if (!empty($stripeReady)): ?>
            <p style="color: #666; margin: 16px 0;">
                You will be redirected to Stripe's secure payment page.
            </p>
            <?= $this->Form->create(null, ['url' => ['action' => 'process', $booking->booking_id]]) ?>
            <?= $this->Form->button('Proceed to Secure Payment', ['class' => 'btn btn-primary', 'style' => 'width: 100%; padding: 12px; font-size: 1.1em;']) ?>
            <?= $this->Form->end() ?>
        <?php else: ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px; margin: 16px 0;">
                <strong>Demo Mode</strong> — Stripe is not configured. Click below to simulate a successful payment.
            </div>
            <?= $this->Form->create(null, ['url' => ['action' => 'process', $booking->booking_id]]) ?>
            <?= $this->Form->button('Complete Demo Payment', ['class' => 'btn btn-primary', 'style' => 'width: 100%; padding: 12px; font-size: 1.1em;']) ?>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </div>
</div>
