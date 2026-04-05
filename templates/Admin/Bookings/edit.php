<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 */
$this->assign('title', 'Edit Booking #' . $booking->booking_id);
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Bookings</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Edit Booking</h5></div>
    <div class="card-body">
        <div class="alert alert-light mb-4">
            <strong>Student:</strong> <?= h($booking->student?->student_name ?? '-') ?> &middot;
            <strong>Course:</strong> <?= h($booking->class_entity?->course?->course_name ?? '-') ?> &middot;
            <strong>Class:</strong> <?= h($booking->class_entity?->class_code ?? '-') ?>
        </div>

        <?= $this->Form->create($booking) ?>
            <div class="mb-3">
                <label for="booking-status" class="form-label">Booking Status</label>
                <?= $this->Form->select('booking_status', ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'], ['id' => 'booking-status', ]) ?>
            </div>
            <div class="mb-3">
                <label for="price-at-booking" class="form-label">Price at Booking ($)</label>
                <?= $this->Form->text('price_at_booking', ['id' => 'price-at-booking', 'type' => 'number', 'step' => '0.01', 'min' => '0', ]) ?>
            </div>
            <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
