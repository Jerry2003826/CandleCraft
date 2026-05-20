<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 */
$bookingLabel = 'BK-' . str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT);
$this->assign('title', 'Edit Booking ' . $bookingLabel);
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Bookings
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit Booking <?= h($bookingLabel) ?></h2>
    </div>
    
    <div class="admin-form-group p-4 mb-4" style="background-color: var(--admin-search-bg); border-radius: 12px;">
        <div class="row g-3">
            <div class="col-md-4">
                <span style="color: var(--admin-text-secondary); font-size: 14px; display: block; margin-bottom: 4px;">Student</span>
                <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= h($booking->student?->student_name ?? '-') ?></strong>
            </div>
            <div class="col-md-4">
                <span style="color: var(--admin-text-secondary); font-size: 14px; display: block; margin-bottom: 4px;">Course</span>
                <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= h($booking->class_entity?->course?->course_name ?? '-') ?></strong>
            </div>
            <div class="col-md-4">
                <span style="color: var(--admin-text-secondary); font-size: 14px; display: block; margin-bottom: 4px;">Class</span>
                <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= h($booking->class_entity?->class_code ?? '-') ?></strong>
            </div>
        </div>
    </div>

    <?= $this->Form->create($booking) ?>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="booking-status" class="admin-form-label">Booking Status</label>
                    <?= $this->Form->select('booking_status', [
                        'pending' => 'Pending', 
                        'confirmed' => 'Confirmed', 
                        'completed' => 'Completed', 
                        'cancelled' => 'Cancelled'
                    ], [
                        'id' => 'booking-status',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="price-at-booking" class="admin-form-label">Price at Booking ($)</label>
                    <?= $this->Form->text('price_at_booking', [
                        'id' => 'price-at-booking', 
                        'type' => 'number', 
                        'step' => '0.01', 
                        'min' => '0',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Save Changes', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
