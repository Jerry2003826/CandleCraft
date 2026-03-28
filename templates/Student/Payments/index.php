<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 */
$this->assign('title', 'Payments');
?>

<div class="card">
    <div class="card-header">
        <h3>My Payments</h3>
    </div>
    <?php if (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4B3;</div>
            <p>No bookings available for payment.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Class</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <?php
                    $hasPaidRecord = false;
                    $needsParentAuth = !empty($booking->parent_id);
                    foreach ($booking->payments ?? [] as $payment) {
                        if ($payment->payment_status === 'paid') {
                            $hasPaidRecord = true;
                            break;
                        }
                    }
                    $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord;
                    ?>
                    <tr>
                        <td><?= h($booking->class_entity?->course?->course_name ?? '-') ?></td>
                        <td><?= h($booking->class_entity?->class_code ?? '-') ?></td>
                        <td>$<?= number_format((float)$booking->price_at_booking, 2) ?></td>
                        <td>
                            <?php if ($isPaid): ?>
                                <span class="badge badge-confirmed">Paid</span>
                            <?php elseif ($needsParentAuth): ?>
                                <span class="badge badge-pending">Awaiting Parent</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$isPaid && !$needsParentAuth && in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="btn btn-sm btn-primary">Pay Now</a>
                            <?php elseif (!$isPaid && $needsParentAuth): ?>
                                <span class="badge badge-read">Parent will pay</span>
                            <?php else: ?>
                                <span class="badge badge-read">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
