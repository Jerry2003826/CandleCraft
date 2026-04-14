<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var string $userRole
 */
$this->assign('title', 'Payments');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="admin-form-title m-0" style="font-size: 18px;">My Payments</h2>
    <button type="button" class="admin-btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentMethodModal">
        <i class="bi bi-plus-lg"></i> Add Payment Method
    </button>
</div>

<?php if (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-credit-card" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="color: var(--admin-text-secondary);">No bookings available for payment.</p>
    </div>
<?php else: ?>
    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <?php if ($userRole === 'parent'): ?><th>Student</th><?php endif; ?>
                        <th>Course</th>
                        <th>Class</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $hasPaidRecord = false;
                        foreach ($booking->payments ?? [] as $payment) {
                            if ($payment->payment_status === 'paid') {
                                $hasPaidRecord = true;
                                break;
                            }
                        }
                        $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord;
                        ?>
                        <tr>
                            <?php if ($userRole === 'parent'): ?>
                                <td><p class="admin-table-primary-text"><?= h($booking->student?->student_name ?? '-') ?></p></td>
                            <?php endif; ?>
                            <td><p class="admin-table-primary-text"><?= h($booking->class_entity?->course?->course_name ?? '-') ?></p></td>
                            <td><p class="admin-table-secondary-text"><?= h($booking->class_entity?->class_code ?? '-') ?></p></td>
                            <td><p class="admin-table-primary-text">$<?= number_format((float)$booking->price_at_booking, 2) ?></p></td>
                            <td>
                                <?php if ($isPaid): ?>
                                    <span class="admin-badge admin-badge-success">Paid</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-warning">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="admin-action-links justify-content-end">
                                    <?php if (!$isPaid && in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                        <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="admin-btn-primary" style="padding: 6px 12px; font-size: 12px;">Pay Now</a>
                                    <?php elseif ($isPaid): ?>
                                        <a href="<?= $this->Url->build(['action' => 'receipt', collection($booking->payments)->last()->payment_id]) ?>" class="admin-action-link view" style="padding: 6px 12px; height: auto; width: auto; font-size: 12px;">Receipt</a>
                                    <?php else: ?>
                                        <span class="text-muted small">No action</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Add Payment Method Modal -->
<div class="modal fade" id="addPaymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--admin-card-border);">
                <h5 class="modal-title admin-form-title">Add Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--bs-btn-close-filter);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="admin-form-group">
                    <label class="admin-form-label">Card Number</label>
                    <div class="position-relative">
                        <i class="bi bi-credit-card position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: var(--admin-text-secondary);"></i>
                        <input type="text" class="admin-form-input" placeholder="0000 0000 0000 0000" style="padding-left: 44px;" maxlength="19">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="admin-form-group mb-0">
                            <label class="admin-form-label">Expiry Date</label>
                            <input type="text" class="admin-form-input" placeholder="MM/YY" maxlength="5">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="admin-form-group mb-0">
                            <label class="admin-form-label">CVC</label>
                            <input type="text" class="admin-form-input" placeholder="123" maxlength="4">
                        </div>
                    </div>
                </div>
                <div class="admin-form-group mt-3 mb-0">
                    <label class="admin-form-label">Name on Card</label>
                    <input type="text" class="admin-form-input" placeholder="John Doe">
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--admin-card-border);">
                <button type="button" class="admin-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="admin-btn-primary" data-bs-dismiss="modal" onclick="alert('Payment method saved successfully!')">Save Card</button>
            </div>
        </div>
    </div>
</div>

<style>
[data-theme="dark"] .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>
