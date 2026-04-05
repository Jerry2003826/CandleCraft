<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 */
$this->assign('title', 'Cancel Booking');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> My Schedule</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Cancel Booking</h5>
    </div>
    <div class="card-body">
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 48px;"></i>
            <p class="mt-3">Are you sure you want to cancel your booking for <strong><?= h($booking->class_entity ? $booking->class_entity->class_code : 'this class') ?></strong>?</p>
            <p class="text-muted">
                Course: <?= h($booking->class_entity && $booking->class_entity->course ? $booking->class_entity->course->course_name : '-') ?><br>
                Price: $<?= number_format((float)$booking->price_at_booking, 2) ?>
            </p>
        </div>

        <?= $this->Form->create(null, ['url' => ['action' => 'cancel', $booking->booking_id]]) ?>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <?= $this->Form->button('Yes, Cancel Booking', ['class' => 'btn btn-danger']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary">No, Go Back</a>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
