<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 * @var string|null $status
 * @var array{total: int, confirmed: int, pending: int, cancelled: int} $stats
 */
$this->assign('title', 'Bookings');
$totalPct = $stats['total'] > 0 ? $stats['total'] : 1;
?>

<!-- Stats Overview Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Total Bookings</h3>
                <div class="admin-stat-icon"><i class="bi bi-journal-text"></i></div>
            </div>
            <div class="admin-stat-value"><?= $stats['total'] ?></div>
            <div class="admin-stat-trend neutral">All time</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Confirmed</h3>
                <div class="admin-stat-icon"><i class="bi bi-check-circle"></i></div>
            </div>
            <div class="admin-stat-value"><?= $stats['confirmed'] ?></div>
            <div class="admin-stat-trend up"><?= $stats['total'] > 0 ? round($stats['confirmed'] / $totalPct * 100, 1) : 0 ?>%</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Pending Payment</h3>
                <div class="admin-stat-icon"><i class="bi bi-clock-history"></i></div>
            </div>
            <div class="admin-stat-value"><?= $stats['pending'] ?></div>
            <div class="admin-stat-trend <?= $stats['pending'] > 0 ? 'down' : 'neutral' ?>">
                <?= $stats['pending'] > 0 ? 'Needs attention' : 'All good' ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Cancelled</h3>
                <div class="admin-stat-icon"><i class="bi bi-x-circle"></i></div>
            </div>
            <div class="admin-stat-value"><?= $stats['cancelled'] ?></div>
            <div class="admin-stat-trend down"><?= $stats['total'] > 0 ? round($stats['cancelled'] / $totalPct * 100, 1) : 0 ?>%</div>
        </div>
    </div>
</div>

<!-- Filters Toolbar -->
<div class="admin-page-header">
    <div class="admin-tabs flex-wrap">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'confirmed']]) ?>" class="admin-tab <?= $status === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'pending']]) ?>" class="admin-tab <?= $status === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'refund_required']]) ?>" class="admin-tab <?= $status === 'refund_required' ? 'active' : '' ?>">Refund Required</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'cancelled']]) ?>" class="admin-tab <?= $status === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>
</div>

<!-- Bookings Table -->
<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>Student</th>
                    <th>Course / Class</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bookings) === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $paymentBadge = ['class' => 'admin-badge-warning', 'label' => 'Payment Pending'];
                        $paymentPriority = [
                            'refund_required' => ['class' => 'admin-badge-warning', 'label' => 'Refund Required', 'rank' => 100],
                            'disputed' => ['class' => 'admin-badge-danger', 'label' => 'Disputed', 'rank' => 95],
                            'paid' => ['class' => 'admin-badge-success', 'label' => 'Payment Paid', 'rank' => 80],
                            'partially_refunded' => ['class' => 'admin-badge-info', 'label' => 'Partially Refunded', 'rank' => 75],
                            'refunded' => ['class' => 'admin-badge-neutral', 'label' => 'Refunded', 'rank' => 70],
                            'failed' => ['class' => 'admin-badge-danger', 'label' => 'Payment Failed', 'rank' => 60],
                            'expired' => ['class' => 'admin-badge-neutral', 'label' => 'Payment Expired', 'rank' => 50],
                            'voided' => ['class' => 'admin-badge-neutral', 'label' => 'Payment Voided', 'rank' => 45],
                        ];
                        $currentRank = 0;
                        foreach ($booking->payments ?? [] as $payment) {
                            $paymentStatus = (string)$payment->payment_status;
                            if (
                                $booking->booking_status === 'cancelled'
                                && in_array($paymentStatus, ['paid', 'partially_refunded', 'disputed'], true)
                                && round((float)$payment->refunded_amount, 2) < round((float)$payment->amount, 2)
                            ) {
                                $paymentStatus = 'refund_required';
                            }
                            $candidate = $paymentPriority[$paymentStatus] ?? null;
                            if ($candidate !== null && $candidate['rank'] > $currentRank) {
                                $paymentBadge = $candidate;
                                $currentRank = $candidate['rank'];
                            }
                        }
                        $viewUrl = $this->Url->build(['action' => 'view', $booking->booking_id]);
                        ?>
                    <tr class="admin-clickable-row" data-href="<?= h($viewUrl) ?>" tabindex="0" role="link" aria-label="View booking BK-<?= str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT) ?>">
                        <td>
                            <a href="<?= h($viewUrl) ?>" class="admin-table-primary-text text-decoration-none" style="color: var(--admin-brand-icon);">
                                BK-<?= str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <td>
                            <p class="admin-table-primary-text"><?= h($booking->student?->student_name ?? '-') ?></p>
                        </td>
                        <td>
                            <p class="admin-table-primary-text"><?= h($booking->class_entity?->course?->course_name ?? '-') ?></p>
                            <p class="admin-table-secondary-text"><?= h($booking->class_entity?->class_code ?? '') ?></p>
                        </td>
                        <td>
                            <?php if ($booking->class_entity?->start_datetime): ?>
                                <p class="admin-table-primary-text"><?= $booking->class_entity->start_datetime->format('D j M') ?></p>
                                <p class="admin-table-secondary-text"><?= $booking->class_entity->start_datetime->format('g:ia') ?></p>
                            <?php else: ?>
                                <p class="admin-table-secondary-text"><?= $booking->booking_date ? $booking->booking_date->format('j M Y') : '-' ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'admin-badge-neutral';
                                if ($booking->booking_status === 'confirmed') $statusClass = 'admin-badge-success';
                                if ($booking->booking_status === 'pending') $statusClass = 'admin-badge-warning';
                                if ($booking->booking_status === 'cancelled') $statusClass = 'admin-badge-danger';
                            ?>
                            <span class="admin-badge <?= $statusClass ?>"><?= h(ucfirst((string)$booking->booking_status)) ?></span>
                        </td>
                        <td>
                            <span class="admin-badge <?= h($paymentBadge['class']) ?>"><?= h($paymentBadge['label']) ?></span>
                        </td>
                        <td>
                            <div class="admin-action-links justify-content-end">
                                <a href="<?= h($viewUrl) ?>" class="admin-action-link view" title="View" aria-label="View booking BK-<?= str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT) ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= $this->Url->build(['action' => 'edit', $booking->booking_id]) ?>" class="admin-action-link edit" title="Edit" aria-label="Edit booking BK-<?= str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-pagination">
        <?= $this->Paginator->prev('<i class="bi bi-chevron-left"></i>', ['escape' => false, 'aria-label' => 'Previous page']) ?>
        <?= $this->Paginator->numbers(['escape' => false]) ?>
        <?= $this->Paginator->next('<i class="bi bi-chevron-right"></i>', ['escape' => false, 'aria-label' => 'Next page']) ?>
    </div>
</div>

<style>
    .admin-clickable-row {
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.admin-clickable-row[data-href]').forEach(function (row) {
        function openRow(event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            window.location.href = row.dataset.href;
        }

        row.addEventListener('click', openRow);
        row.addEventListener('keydown', function (event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            window.location.href = row.dataset.href;
        });
    });
});
</script>
