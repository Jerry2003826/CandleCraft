<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 * @var array $children
 */
$this->assign('title', 'Family Bookings');
?>

<div class="card">
    <div class="card-header">
        <h3>Family Bookings</h3>
        <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm btn-primary">Browse Courses</a>
    </div>
    <?php if (empty($children)): ?>
        <div class="empty-state">
            <div class="icon">&#x1F476;</div>
            <p>No linked children found. Please contact admin.</p>
        </div>
    <?php elseif (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4C5;</div>
            <p>No bookings yet. Browse courses and book for your child.</p>
        </div>
    <?php else: ?>
        <div class="portal-class-list">
            <?php foreach ($bookings as $booking): ?>
                <section class="portal-class-card">
                    <div class="portal-class-card__header">
                        <div>
                            <p class="portal-class-card__eyebrow">
                                <?= h($booking->student?->student_name ?? 'Student') ?>
                            </p>
                            <h3><?= h($booking->class_entity?->course?->course_name ?? $booking->class_entity?->class_code ?? 'Class') ?></h3>
                        </div>
                        <span class="badge badge-<?= h($booking->booking_status) ?>">
                            <?= ucfirst(h($booking->booking_status)) ?>
                        </span>
                    </div>
                    <div class="portal-class-card__meta">
                        <span><?= h($booking->class_entity?->class_code ?? '-') ?></span>
                        <span><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('D j M, g:ia') : '-' ?></span>
                        <span><?= h($booking->class_entity?->location ?? '-') ?></span>
                    </div>
                    <div class="portal-status-row">
                        <div>
                            <span class="portal-status-row__label">Price</span>
                            <strong>$<?= number_format((float)$booking->price_at_booking, 2) ?></strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <?php
                            $hasPaidRecord = false;
                            foreach ($booking->payments ?? [] as $payment) {
                                if ($payment->payment_status === 'paid') {
                                    $hasPaidRecord = true;
                                }
                            }
                            $hasPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord;
                            ?>
                            <?php if ($booking->booking_status === 'pending' && !$hasPaid): ?>
                                <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'process', $booking->booking_id]) ?>" class="btn btn-sm btn-primary">Pay Now</a>
                            <?php endif; ?>
                            <?php if (in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                <?= $this->Form->postLink(
                                    'Cancel',
                                    ['action' => 'cancel', $booking->booking_id],
                                    ['class' => 'btn btn-sm', 'confirm' => 'Are you sure you want to cancel this booking?']
                                ) ?>
                            <?php endif; ?>
                            <?php if ($hasPaid): ?>
                                <span class="badge badge-confirmed">Paid</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
