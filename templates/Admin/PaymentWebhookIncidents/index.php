<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\PaymentWebhookIncident> $incidents
 * @var string $status
 */
$this->assign('title', 'Webhook Incidents');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <p style="font-size: 13px; color: var(--admin-text-secondary); margin: 0;">Manual-review and non-retriable Stripe webhook conflicts that need follow-up.</p>
    <div class="d-flex gap-2">
        <?php foreach (['open' => 'Open', 'resolved' => 'Resolved', 'ignored' => 'Ignored', 'all' => 'All'] as $value => $label): ?>
            <?= $this->Html->link(
                $label,
                ['action' => 'index', '?' => ['status' => $value]],
                [
                    'class' => 'admin-tab' . ($status === $value ? ' active' : ''),
                ]
            ) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Created</th>
                    <th>Severity</th>
                    <th>Reason</th>
                    <th>Event</th>
                    <th>Session</th>
                    <th>Payment</th>
                    <th>Booking</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($incidents) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px;">No incidents found for this filter.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($incidents as $incident): ?>
                    <tr>
                        <td><p class="admin-table-secondary-text"><?= h($incident->created_at?->format('j M Y, H:i')) ?></p></td>
                        <td>
                            <?php $sevClass = $incident->severity === 'error' ? 'admin-badge-danger' : 'admin-badge-warning'; ?>
                            <span class="admin-badge <?= $sevClass ?>"><?= h(ucfirst((string)$incident->severity)) ?></span>
                        </td>
                        <td><p class="admin-table-secondary-text"><?= h($incident->reason_code) ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= h($incident->event_type) ?></p></td>
                        <td><p class="admin-table-secondary-text" style="font-family: monospace; font-size: 12px;"><?= h($incident->session_id) ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= $incident->payment_id !== null ? (int)$incident->payment_id : '&mdash;' ?></p></td>
                        <td><p class="admin-table-secondary-text"><?= $incident->booking_id !== null ? (int)$incident->booking_id : '&mdash;' ?></p></td>
                        <td>
                            <?php
                                $incStatusClass = 'admin-badge-neutral';
                                if ($incident->status === 'open') $incStatusClass = 'admin-badge-warning';
                                if ($incident->status === 'resolved') $incStatusClass = 'admin-badge-success';
                                if ($incident->status === 'ignored') $incStatusClass = 'admin-badge-neutral';
                            ?>
                            <span class="admin-badge <?= $incStatusClass ?>"><?= h(ucfirst((string)$incident->status)) ?></span>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($incident->status === 'open'): ?>
                                <div class="d-flex gap-2 justify-content-end">
                                    <?= $this->Form->postLink(
                                        'Resolve',
                                        ['action' => 'resolve', $incident->incident_id],
                                        ['class' => 'admin-action-link admin-action-link--view', 'style' => 'font-size: 12px; padding: 4px 10px;']
                                    ) ?>
                                    <?= $this->Form->postLink(
                                        'Ignore',
                                        ['action' => 'ignore', $incident->incident_id],
                                        ['class' => 'admin-action-link admin-action-link--delete', 'style' => 'font-size: 12px; padding: 4px 10px;']
                                    ) ?>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 12px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif;">Handled</span>
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
