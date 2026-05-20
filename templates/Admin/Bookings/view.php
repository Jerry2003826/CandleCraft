<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Booking $booking
 */
$bookingLabel = 'BK-' . str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT);
$this->assign('title', 'View Booking ' . $bookingLabel);
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <a href="#" onclick="history.back(); return false;" class="admin-back-link mb-0">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <a href="<?= $this->Url->build(['action' => 'edit', $booking->booking_id]) ?>" class="admin-btn-secondary" style="color: #D97706; padding: 6px 16px; font-size: 13px;">
        <i class="bi bi-pencil me-1"></i> Edit Booking
    </a>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
        <h2 class="admin-form-title" style="font-size: 20px; margin: 0;">
            <?= h($bookingLabel) ?>
        </h2>
        <?php
            $statusClass = 'admin-badge-neutral';
            if ($booking->booking_status === 'confirmed') $statusClass = 'admin-badge-success';
            if ($booking->booking_status === 'pending') $statusClass = 'admin-badge-warning';
            if ($booking->booking_status === 'cancelled') $statusClass = 'admin-badge-danger';
        ?>
        <span class="admin-badge <?= $statusClass ?>" style="padding: 6px 12px; font-size: 13px;"><?= h(ucfirst((string)$booking->booking_status)) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <?php
            $fields = [
                'Booking ID' => h((string)$booking->booking_id),
                'Student'    => h($booking->student?->student_name ?? '-'),
                'Course'     => h($booking->class_entity?->course?->course_name ?? '-'),
                'Class Code' => h($booking->class_entity?->class_code ?? '-'),
                'Teacher'    => h($booking->class_entity?->teacher?->teacher_name ?? '-'),
            ];
            foreach ($fields as $label => $value): ?>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;"><?= $label ?></div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $value ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Schedule</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('D j M Y, g:ia') : '-' ?>
                    <?= $booking->class_entity?->end_datetime ? ' &ndash; ' . $booking->class_entity->end_datetime->format('g:ia') : '' ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Location</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($booking->class_entity?->location ?? '-') ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Price at Booking</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">$<?= number_format((float)$booking->price_at_booking, 2) ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Booking Date</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $booking->booking_date ? $booking->booking_date->format('j M Y, g:ia') : '-' ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($booking->payments)): ?>
<div class="admin-table-card mb-4">
    <div style="padding: 20px 24px; border-bottom: 1.5px solid var(--admin-card-border); background: rgba(210, 154, 88, 0.03);">
        <h3 class="admin-form-title" style="font-size: 18px; margin: 0;">Payment Records</h3>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Refunded</th>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Stripe</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($booking->payments as $payment): ?>
                <?php
                    $paymentStatusMap = [
                        'paid' => ['class' => 'admin-badge-success', 'label' => 'Paid'],
                        'pending' => ['class' => 'admin-badge-warning', 'label' => 'Pending'],
                        'failed' => ['class' => 'admin-badge-danger', 'label' => 'Failed'],
                        'expired' => ['class' => 'admin-badge-neutral', 'label' => 'Expired'],
                        'voided' => ['class' => 'admin-badge-neutral', 'label' => 'Voided'],
                        'refund_required' => ['class' => 'admin-badge-warning', 'label' => 'Refund Required'],
                        'refunded' => ['class' => 'admin-badge-neutral', 'label' => 'Refunded'],
                        'partially_refunded' => ['class' => 'admin-badge-info', 'label' => 'Partially Refunded'],
                        'disputed' => ['class' => 'admin-badge-danger', 'label' => 'Disputed'],
                    ];
                    $paymentStatus = $paymentStatusMap[$payment->payment_status] ?? ['class' => 'admin-badge-neutral', 'label' => ucfirst((string)$payment->payment_status)];
                    $refundableRemaining = max(0, round((float)$payment->amount - (float)$payment->refunded_amount, 2));
                    $canRefund = in_array((string)$payment->payment_status, ['paid', 'partially_refunded', 'refund_required', 'disputed'], true) && $refundableRemaining > 0;
                ?>
                <tr>
                    <td><p class="admin-table-primary-text"><?= h((string)$payment->payment_id) ?></p></td>
                    <td><p class="admin-table-primary-text">$<?= number_format((float)$payment->amount, 2) ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= ucfirst(h($payment->payment_method ?? '-')) ?></p></td>
                    <td>
                        <span class="admin-badge <?= $paymentStatus['class'] ?>"><?= h($paymentStatus['label']) ?></span>
                    </td>
                    <td><p class="admin-table-secondary-text">$<?= number_format((float)$payment->refunded_amount, 2) ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= $payment->payment_date ? $payment->payment_date->format('j M Y, g:ia') : '-' ?></p></td>
                    <td><p class="admin-table-secondary-text" style="font-family: monospace; font-size: 12px;"><?= h($payment->transaction_reference ?? '-') ?></p></td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <?php if (!empty($payment->stripe_invoice_pdf_url)): ?>
                                <a href="<?= h($payment->stripe_invoice_pdf_url) ?>" target="_blank" rel="noopener" class="admin-action-link admin-action-link--view" style="font-size: 12px; padding: 4px 10px;">Invoice</a>
                            <?php endif; ?>
                            <?php if (!empty($payment->stripe_receipt_url)): ?>
                                <a href="<?= h($payment->stripe_receipt_url) ?>" target="_blank" rel="noopener" class="admin-action-link admin-action-link--view" style="font-size: 12px; padding: 4px 10px;">Receipt</a>
                            <?php endif; ?>
                            <?php if (empty($payment->stripe_invoice_pdf_url) && empty($payment->stripe_receipt_url)): ?>
                                <span style="font-size: 12px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif;">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($canRefund): ?>
                            <?= $this->Form->create(null, [
                                'url' => ['action' => 'refundPayment', $booking->booking_id, $payment->payment_id],
                                'class' => 'd-flex gap-2 justify-content-end align-items-center',
                            ]) ?>
                                <?= $this->Form->control('amount', [
                                    'type' => 'number',
                                    'label' => false,
                                    'value' => number_format($refundableRemaining, 2, '.', ''),
                                    'min' => '0.01',
                                    'max' => number_format($refundableRemaining, 2, '.', ''),
                                    'step' => '0.01',
                                    'style' => 'width: 96px; font-size: 12px;',
                                ]) ?>
                                <?= $this->Form->control('reason', [
                                    'type' => 'hidden',
                                    'value' => 'requested_by_customer',
                                ]) ?>
                                <?= $this->Form->button('Refund', [
                                    'class' => 'admin-action-link admin-action-link--delete',
                                    'style' => 'font-size: 12px; padding: 4px 10px; border: 0;',
                                ]) ?>
                            <?= $this->Form->end() ?>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif;">No action</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($booking->attendance_records)): ?>
<div class="admin-table-card">
    <div style="padding: 20px 24px; border-bottom: 1.5px solid var(--admin-card-border); background: rgba(210, 154, 88, 0.03);">
        <h3 class="admin-form-title" style="font-size: 18px; margin: 0;">Attendance Records</h3>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($booking->attendance_records as $record): ?>
                <tr>
                    <td>
                        <?php
                            $aStatusClass = 'admin-badge-neutral';
                            if ($record->attendance_status === 'present') $aStatusClass = 'admin-badge-success';
                            if ($record->attendance_status === 'absent') $aStatusClass = 'admin-badge-danger';
                            if ($record->attendance_status === 'late') $aStatusClass = 'admin-badge-warning';
                        ?>
                        <span class="admin-badge <?= $aStatusClass ?>"><?= ucfirst(h($record->attendance_status)) ?></span>
                    </td>
                    <td><p class="admin-table-secondary-text"><?= h($record->note ?? '-') ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= $record->created ? $record->created->format('j M Y, g:ia') : '-' ?></p></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
