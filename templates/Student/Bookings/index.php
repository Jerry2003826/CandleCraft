<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 */
$this->assign('title', 'My Bookings');
?>

<div class="card">
    <div class="card-header">
        <h3>My Bookings</h3>
        <a href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm btn-primary">Browse Courses</a>
    </div>
    <?php if ($bookings->isEmpty()): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4D6;</div>
            <p>You have no bookings yet. Browse our courses to get started!</p>
        </div>
    <?php else: ?>
        <div class="portal-class-list">
            <?php foreach ($bookings as $booking): ?>
                <section class="portal-class-card">
                    <div class="portal-class-card__header">
                        <div>
                            <p class="portal-class-card__eyebrow">
                                <?= h($booking->class_entity ? $booking->class_entity->class_code : 'Class') ?>
                            </p>
                            <h3>
                                <?= h($booking->class_entity && $booking->class_entity->course
                                    ? $booking->class_entity->course->course_name
                                    : 'Class') ?>
                            </h3>
                        </div>
                        <span class="badge badge-<?= h($booking->booking_status) ?>">
                            <?= ucfirst(h($booking->booking_status)) ?>
                        </span>
                    </div>

                    <div class="portal-class-card__meta">
                        <span>
                            <?= $booking->class_entity && $booking->class_entity->start_datetime
                                ? $booking->class_entity->start_datetime->format('D j M, g:ia')
                                : '-' ?>
                        </span>
                        <span><?= h($booking->class_entity ? $booking->class_entity->location : '-') ?></span>
                        <span>
                            Teacher:
                            <?= h($booking->class_entity && $booking->class_entity->teacher
                                ? $booking->class_entity->teacher->teacher_name
                                : '-') ?>
                        </span>
                    </div>

                    <div class="portal-status-row">
                        <div>
                            <span class="portal-status-row__label">Price</span>
                            <strong>$<?= number_format((float)$booking->price_at_booking, 2) ?></strong>
                        </div>
                        <div>
                            <span class="portal-status-row__label">Payment</span>
                            <strong>
                                <?php
                                $latestPayment = null;
                                if (!empty($booking->payments)) {
                                    $latestPayment = collection($booking->payments)->last();
                                }
                                echo $latestPayment ? ucfirst(h($latestPayment->payment_status)) : 'Pending';
                                ?>
                            </strong>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php
                            $isPaid = false;
                            if (!empty($booking->payments)) {
                                foreach ($booking->payments as $p) {
                                    if ($p->payment_status === 'paid') {
                                        $isPaid = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <?php if ($booking->booking_status === 'pending' && !$isPaid && empty($booking->parent_id)): ?>
                                <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Payments', 'action' => 'process', $booking->booking_id]) ?>"
                                   class="btn btn-sm btn-primary">Pay Now</a>
                            <?php elseif ($booking->booking_status === 'pending' && !$isPaid && !empty($booking->parent_id)): ?>
                                <span class="badge badge-pending">Parent Authorization Required</span>
                            <?php endif; ?>
                            <?php if (in_array($booking->booking_status, ['pending', 'confirmed'])): ?>
                                <?= $this->Form->postLink(
                                    'Cancel',
                                    ['action' => 'cancel', $booking->booking_id],
                                    ['class' => 'btn btn-sm', 'confirm' => 'Are you sure you want to cancel this booking?']
                                ) ?>
                            <?php endif; ?>
                            <?php if ($isPaid): ?>
                                <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Payments', 'action' => 'receipt', collection($booking->payments)->last()->payment_id]) ?>"
                                   class="btn btn-sm">Receipt</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
