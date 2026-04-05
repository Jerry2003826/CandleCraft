<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 * @var string|null $status
 */
$this->assign('title', 'Bookings');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <label class="form-label mb-0">Status:</label>
        <select class="form-select form-select-sm" style="width:auto;" onchange="window.location='<?= $this->Url->build(['action' => 'index']) ?>?status='+this.value">
            <option value="">All</option>
            <?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Bookings</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>ID</th><th>Student</th><th>Course</th><th>Class</th><th>Status</th><th>Payment</th><th>Booked</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $hasPaidRecord = false;
                        foreach ($booking->payments ?? [] as $payment) {
                            if ($payment->payment_status === 'paid') { $hasPaidRecord = true; break; }
                        }
                        ?>
                    <tr>
                        <td><?= h((string)$booking->booking_id) ?></td>
                        <td><?= h($booking->student?->student_name ?? '-') ?></td>
                        <td><?= h($booking->class_entity?->course?->course_name ?? '-') ?></td>
                        <td><?= h($booking->class_entity?->class_code ?? '-') ?></td>
                        <td><?= $this->Badge->status($booking->booking_status) ?></td>
                        <td>
                            <?php if ($hasPaidRecord): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $booking->booking_date ? $booking->booking_date->format('j M Y') : '-' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= $this->Url->build(['action' => 'view', $booking->booking_id]) ?>" class="btn btn-outline-primary">View</a>
                                <a href="<?= $this->Url->build(['action' => 'edit', $booking->booking_id]) ?>" class="btn btn-outline-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
