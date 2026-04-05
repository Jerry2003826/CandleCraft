<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 */
$this->assign('title', 'View Booking #' . $booking->booking_id);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Bookings</a>
    <a href="<?= $this->Url->build(['action' => 'edit', $booking->booking_id]) ?>" class="btn btn-outline-primary btn-sm">Edit Booking</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Booking Details</h5>
        <?= $this->Badge->status($booking->booking_status) ?>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th class="text-end text-muted" style="width:180px">Booking ID</th><td><?= h((string)$booking->booking_id) ?></td></tr>
            <tr><th class="text-end text-muted">Student</th><td><?= h($booking->student?->student_name ?? '-') ?></td></tr>
            <tr><th class="text-end text-muted">Course</th><td><?= h($booking->class_entity?->course?->course_name ?? '-') ?></td></tr>
            <tr><th class="text-end text-muted">Class Code</th><td><?= h($booking->class_entity?->class_code ?? '-') ?></td></tr>
            <tr><th class="text-end text-muted">Teacher</th><td><?= h($booking->class_entity?->teacher?->teacher_name ?? '-') ?></td></tr>
            <tr><th class="text-end text-muted">Schedule</th><td>
                <?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('D j M Y, g:ia') : '-' ?>
                <?= $booking->class_entity?->end_datetime ? ' – ' . $booking->class_entity->end_datetime->format('g:ia') : '' ?>
            </td></tr>
            <tr><th class="text-end text-muted">Location</th><td><?= h($booking->class_entity?->location ?? '-') ?></td></tr>
            <tr><th class="text-end text-muted">Price at Booking</th><td>$<?= number_format((float)$booking->price_at_booking, 2) ?></td></tr>
            <tr><th class="text-end text-muted">Booking Date</th><td><?= $booking->booking_date ? $booking->booking_date->format('j M Y, g:ia') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Status</th><td><?= $this->Badge->status($booking->booking_status) ?></td></tr>
        </table>
    </div>
</div>

<?php if (!empty($booking->payments)): ?>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Payment Records</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Payment ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Reference</th></tr></thead>
            <tbody>
                <?php foreach ($booking->payments as $payment): ?>
                <tr>
                    <td><?= h((string)$payment->payment_id) ?></td>
                    <td>$<?= number_format((float)$payment->amount, 2) ?></td>
                    <td><?= ucfirst(h($payment->payment_method ?? '-')) ?></td>
                    <td>
                        <span class="badge <?= $payment->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= $payment->payment_status === 'paid' ? 'Paid' : 'Unpaid' ?>
                        </span>
                    </td>
                    <td><?= $payment->payment_date ? $payment->payment_date->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= h($payment->transaction_reference ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($booking->attendance_records)): ?>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Attendance Records</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Status</th><th>Notes</th><th>Recorded</th></tr></thead>
            <tbody>
                <?php foreach ($booking->attendance_records as $record): ?>
                <tr>
                    <td>
                        <span class="badge <?= $record->attendance_status === 'present' ? 'bg-success' : ($record->attendance_status === 'absent' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                            <?= ucfirst(h($record->attendance_status)) ?>
                        </span>
                    </td>
                    <td><?= h($record->note ?? '-') ?></td>
                    <td><?= $record->created ? $record->created->format('j M Y, g:ia') : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
