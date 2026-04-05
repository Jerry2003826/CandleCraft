<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 */
$this->assign('title', 'Payments');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Payments</h5>
    </div>
    <?php if (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-credit-card" style="font-size: 48px;"></i>
            <p class="mt-3">No bookings available for payment.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                                    <span class="badge badge-read">Managed by Parent</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$isPaid && !$needsParentAuth && in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                    <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="btn btn-sm btn-primary">Pay Now</a>
                                <?php elseif (!$isPaid && $needsParentAuth): ?>
                                    <span class="text-muted small">Parent will pay</span>
                                <?php elseif ($isPaid): ?>
                                    <a href="<?= $this->Url->build(['action' => 'receipt', collection($booking->payments)->last()->payment_id]) ?>" class="btn btn-sm btn-outline-primary">Receipt</a>
                                <?php else: ?>
                                    <span class="text-muted small">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
