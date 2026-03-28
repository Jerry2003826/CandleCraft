<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 */
$this->assign('title', 'Cancel Booking');
?>

<div class="card">
    <div class="card-header">
        <h3>Cancel Booking</h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; My Bookings</a>
    </div>
    <div class="card-body">
        <div class="empty-state">
            <div class="icon">&#x26A0;&#xFE0F;</div>
            <p>Are you sure you want to cancel your booking for <strong><?= h($booking->class_entity ? $booking->class_entity->class_code : 'this class') ?></strong>?</p>
            <p style="color: #666;">
                Course: <?= h($booking->class_entity && $booking->class_entity->course ? $booking->class_entity->course->course_name : '-') ?><br>
                Price: $<?= number_format((float)$booking->price_at_booking, 2) ?>
            </p>
        </div>

        <?= $this->Form->create(null, ['url' => ['action' => 'cancel', $booking->booking_id]]) ?>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
            <?= $this->Form->button('Yes, Cancel Booking', ['class' => 'btn btn-primary', 'style' => 'background: #e74c3c;']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn">No, Go Back</a>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
