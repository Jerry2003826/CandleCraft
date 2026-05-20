<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\PaymentDispute> $disputes
 * @var string $status
 */
$this->assign('title', 'Payment Disputes');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <p style="font-size: 13px; color: var(--admin-text-secondary); margin: 0;">Stripe disputes and chargebacks that require time-sensitive operational review.</p>
    <div class="d-flex gap-2">
        <?php foreach (['open' => 'Open', 'closed' => 'Closed', 'all' => 'All'] as $value => $label): ?>
            <?= $this->Html->link($label, ['action' => 'index', '?' => ['status' => $value]], [
                'class' => 'admin-tab' . ($status === $value ? ' active' : ''),
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Opened</th>
                    <th>Due</th>
                    <th>Dispute</th>
                    <th>Payment</th>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($disputes) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px;">No disputes found for this filter.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($disputes as $dispute): ?>
                    <?php
                        $statusClass = in_array((string)$dispute->status, ['won', 'warning_closed'], true) ? 'admin-badge-success' : 'admin-badge-warning';
                        if ((string)$dispute->status === 'lost') {
                            $statusClass = 'admin-badge-danger';
                        }
                        $payment = $dispute->payment ?? null;
                        $booking = $payment?->booking ?? null;
                    ?>
                    <tr>
                        <td><p class="admin-table-secondary-text"><?= h($dispute->opened_at?->format('j M Y, H:i') ?? '-') ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= h($dispute->evidence_due_by?->format('j M Y, H:i') ?? '-') ?></p></td>
                        <td><p class="admin-table-secondary-text" style="font-family: monospace; font-size: 12px;"><?= h($dispute->stripe_dispute_id) ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= $payment ? '#' . h((string)$payment->payment_id) : '-' ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= h($booking?->student?->student_name ?? '-') ?></p></td>
                        <td><p class="admin-table-primary-text">$<?= number_format((float)$dispute->amount, 2) ?> <?= h($dispute->currency_code) ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= h($dispute->reason ?: '-') ?></p></td>
                        <td><span class="admin-badge <?= $statusClass ?>"><?= h(str_replace('_', ' ', ucfirst((string)$dispute->status))) ?></span></td>
                        <td style="text-align: right;">
                            <a href="https://dashboard.stripe.com/disputes/<?= rawurlencode((string)$dispute->stripe_dispute_id) ?>" target="_blank" rel="noopener" class="admin-action-link admin-action-link--view" style="font-size: 12px; padding: 4px 10px;">Stripe</a>
                            <?php if ($payment): ?>
                                <?= $this->Html->link('Booking', ['controller' => 'Bookings', 'action' => 'view', $payment->booking_id], [
                                    'class' => 'admin-action-link admin-action-link--view',
                                    'style' => 'font-size: 12px; padding: 4px 10px;',
                                ]) ?>
                            <?php else: ?>
                                <span style="font-size: 12px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif;">Unmatched</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($this->Paginator->hasPage(2)): ?>
    <div class="d-flex justify-content-center mt-4">
        <div class="pagination">
            <?= $this->Paginator->prev('&laquo; Prev') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next &raquo;') ?>
        </div>
    </div>
<?php endif; ?>
