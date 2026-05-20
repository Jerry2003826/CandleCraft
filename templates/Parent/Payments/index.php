<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 */
$this->assign('title', 'Payments');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Family Payments</h5>
    </div>
    <div class="card-body pb-0">
        <p class="text-muted">Child bookings that require payment authorization appear here. Complete payment to confirm the booking.</p>
    </div>
    <?php if (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-credit-card" style="font-size: 48px;"></i>
            <p class="mt-3">No bookings to pay yet.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $hasPaidRecord = false;
                        $latestPaidPayment = null;
                        $latestPaymentStatus = 'pending';
                        $refundablePayment = null;
                        foreach ($booking->payments ?? [] as $payment) {
                            $latestPaymentStatus = (string)$payment->payment_status;
                            if ($payment->payment_status === 'paid') {
                                $hasPaidRecord = true;
                                if (
                                    $latestPaidPayment === null ||
                                    (int)$payment->payment_id > (int)$latestPaidPayment->payment_id
                                ) {
                                    $latestPaidPayment = $payment;
                                }
                            }
                            if (
                                in_array((string)$payment->payment_status, ['paid', 'partially_refunded'], true)
                                && round((float)$payment->refunded_amount, 2) < round((float)$payment->amount, 2)
                            ) {
                                $refundablePayment = $payment;
                            }
                        }
                        $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord;
                        $canPay = !$isPaid
                            && in_array($booking->booking_status, ['pending', 'confirmed'], true)
                            && !in_array($latestPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded', 'disputed'], true);
                        ?>
                        <tr>
                            <td><?= h($booking->student?->student_name ?? '-') ?></td>
                            <td><?= h($booking->class_entity?->course?->course_name ?? '-') ?></td>
                            <td>$<?= number_format((float)$booking->price_at_booking, 2) ?></td>
                            <td>
                                <?php if ($latestPaymentStatus === 'refund_required'): ?>
                                    <span class="badge badge-pending">Refund Required</span>
                                <?php elseif ($latestPaymentStatus === 'partially_refunded'): ?>
                                    <span class="badge badge-pending">Partially Refunded</span>
                                <?php elseif ($latestPaymentStatus === 'refunded'): ?>
                                    <span class="badge badge-read">Refunded</span>
                                <?php elseif ($isPaid): ?>
                                    <span class="badge badge-confirmed">Payment Paid</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Payment Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canPay): ?>
                                    <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="z-billing-btn-primary" style="min-height: 32px; padding: 7px 14px; font-size: 13px;">Pay Now</a>
                                <?php elseif ($refundablePayment): ?>
                                    <div class="d-flex gap-2 align-items-center">
                                        <a href="<?= $this->Url->build(['action' => 'receipt', $refundablePayment->payment_id]) ?>" class="btn btn-sm btn-outline-primary">Receipt</a>
                                        <?= $this->Form->postLink('Request Refund', ['action' => 'requestRefund', $refundablePayment->payment_id], [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'confirm' => 'Submit this payment for admin refund review?',
                                        ]) ?>
                                    </div>
                                <?php elseif ($latestPaymentStatus === 'refund_required'): ?>
                                    <span class="text-muted small">Refund requested</span>
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
