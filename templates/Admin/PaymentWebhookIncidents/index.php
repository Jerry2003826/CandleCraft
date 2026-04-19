<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\PaymentWebhookIncident> $incidents
 * @var string $status
 */
$this->assign('title', 'Webhook Incidents');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1">Webhook Incidents</h2>
        <p class="text-muted mb-0">Manual-review and non-retriable Stripe webhook conflicts that need follow-up.</p>
    </div>
    <div class="btn-group" role="group" aria-label="Incident status filter">
        <?php foreach (['open' => 'Open', 'resolved' => 'Resolved', 'ignored' => 'Ignored', 'all' => 'All'] as $value => $label): ?>
            <?= $this->Html->link(
                $label,
                ['action' => 'index', '?' => ['status' => $value]],
                ['class' => 'btn btn-sm ' . ($status === $value ? 'btn-primary' : 'btn-outline-secondary')]
            ) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($incidents) === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No incidents found for this filter.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($incidents as $incident): ?>
                        <tr>
                            <td><?= h($incident->created_at?->format('Y-m-d H:i')) ?></td>
                            <td>
                                <span class="badge <?= $incident->severity === 'error' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= h($incident->severity) ?>
                                </span>
                            </td>
                            <td><?= h($incident->reason_code) ?></td>
                            <td><?= h($incident->event_type) ?></td>
                            <td><code><?= h($incident->session_id) ?></code></td>
                            <td><?= $incident->payment_id !== null ? (int)$incident->payment_id : '—' ?></td>
                            <td><?= $incident->booking_id !== null ? (int)$incident->booking_id : '—' ?></td>
                            <td><?= h($incident->status) ?></td>
                            <td class="text-end">
                                <?php if ($incident->status === 'open'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <?= $this->Form->postLink(
                                            'Resolve',
                                            ['action' => 'resolve', $incident->incident_id],
                                            ['class' => 'btn btn-outline-success']
                                        ) ?>
                                        <?= $this->Form->postLink(
                                            'Ignore',
                                            ['action' => 'ignore', $incident->incident_id],
                                            ['class' => 'btn btn-outline-secondary']
                                        ) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Handled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($this->Paginator->hasPage(2)): ?>
    <div class="d-flex justify-content-center mt-4">
        <div class="pagination">
            <?= $this->Paginator->prev('Previous') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next') ?>
        </div>
    </div>
<?php endif; ?>
