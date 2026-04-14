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
                <?php if (empty($bookings) || (is_object($bookings) && $bookings->isEmpty())): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $hasPaidRecord = false;
                        foreach ($booking->payments ?? [] as $payment) {
                            if ($payment->payment_status === 'paid') { $hasPaidRecord = true; break; }
                        }
                        ?>
                    <tr>
                        <td>
                            <a href="<?= $this->Url->build(['action' => 'view', $booking->booking_id]) ?>" class="admin-table-primary-text text-decoration-none" style="color: var(--admin-brand-icon);">
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
                                if ($booking->booking_status === 'Confirmed') $statusClass = 'admin-badge-success';
                                if ($booking->booking_status === 'Pending') $statusClass = 'admin-badge-warning';
                                if ($booking->booking_status === 'Cancelled') $statusClass = 'admin-badge-danger';
                            ?>
                            <span class="admin-badge <?= $statusClass ?>"><?= h($booking->booking_status) ?></span>
                        </td>
                        <td>
                            <?php if ($hasPaidRecord): ?>
                                <span class="admin-badge admin-badge-success">Paid</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge-warning">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="admin-action-links justify-content-end">
                                <a href="<?= $this->Url->build(['action' => 'view', $booking->booking_id]) ?>" class="admin-action-link view" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= $this->Url->build(['action' => 'edit', $booking->booking_id]) ?>" class="admin-action-link edit" title="Edit">
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
        <?= $this->Paginator->prev('<i class="bi bi-chevron-left"></i>', ['escape' => false]) ?>
        <?= $this->Paginator->numbers(['escape' => false]) ?>
        <?= $this->Paginator->next('<i class="bi bi-chevron-right"></i>', ['escape' => false]) ?>
    </div>
</div>
