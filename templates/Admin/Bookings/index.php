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
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-primary">
            <div class="card-body">
                <div class="stat-label">Total Bookings</div>
                <div class="d-flex align-items-end gap-2">
                    <span class="stat-value"><?= $stats['total'] ?></span>
                </div>
                <small class="text-muted">All time</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-success">
            <div class="card-body">
                <div class="stat-label">Confirmed</div>
                <div class="d-flex align-items-end gap-2">
                    <span class="stat-value"><?= $stats['confirmed'] ?></span>
                    <span class="text-muted mb-1"><?= $stats['total'] > 0 ? round($stats['confirmed'] / $totalPct * 100, 1) : 0 ?>%</span>
                </div>
                <div class="progress mt-1" style="height:4px">
                    <div class="progress-bar bg-success" style="width:<?= $stats['total'] > 0 ? round($stats['confirmed'] / $totalPct * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-warning">
            <div class="card-body">
                <div class="stat-label">Pending Payment</div>
                <div class="d-flex align-items-end gap-2">
                    <span class="stat-value"><?= $stats['pending'] ?></span>
                    <?php if ($stats['pending'] > 0): ?>
                        <span class="text-warning mb-1">Needs attention</span>
                    <?php endif; ?>
                </div>
                <div class="progress mt-1" style="height:4px">
                    <div class="progress-bar bg-warning" style="width:<?= $stats['total'] > 0 ? round($stats['pending'] / $totalPct * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-danger">
            <div class="card-body">
                <div class="stat-label">Cancelled</div>
                <div class="d-flex align-items-end gap-2">
                    <span class="stat-value"><?= $stats['cancelled'] ?></span>
                    <span class="text-muted mb-1"><?= $stats['total'] > 0 ? round($stats['cancelled'] / $totalPct * 100, 1) : 0 ?>%</span>
                </div>
                <div class="progress mt-1" style="height:4px">
                    <div class="progress-bar bg-danger" style="width:<?= $stats['total'] > 0 ? round($stats['cancelled'] / $totalPct * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Toolbar -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" class="flex-grow-1" style="max-width:320px">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= h($status) ?>"><?php endif; ?>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search bookings..." value="<?= h($search ?? '') ?>">
        </div>
    </form>
    <div class="btn-group btn-group-sm" role="group">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn <?= !$status ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'confirmed']]) ?>" class="btn <?= $status === 'confirmed' ? 'btn-primary' : 'btn-outline-primary' ?>">Confirmed</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'pending']]) ?>" class="btn <?= $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">Pending</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'cancelled']]) ?>" class="btn <?= $status === 'cancelled' ? 'btn-primary' : 'btn-outline-primary' ?>">Cancelled</a>
    </div>
    <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" disabled>Export</button>
    </div>
</div>

<!-- Bookings Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>Student</th>
                    <th>Course / Class</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
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
                            <a href="<?= $this->Url->build(['action' => 'view', $booking->booking_id]) ?>" class="fw-semibold text-decoration-none" style="color:var(--cc-primary)">
                                BK-<?= str_pad((string)$booking->booking_id, 3, '0', STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="booking-avatar">
                                    <?= strtoupper(substr(h($booking->student?->student_name ?? '?'), 0, 1)) ?>
                                </div>
                                <span><?= h($booking->student?->student_name ?? '-') ?></span>
                            </div>
                        </td>
                        <td>
                            <div><?= h($booking->class_entity?->course?->course_name ?? '-') ?></div>
                            <small class="text-muted"><?= h($booking->class_entity?->class_code ?? '') ?></small>
                        </td>
                        <td>
                            <?php if ($booking->class_entity?->start_datetime): ?>
                                <div><?= $booking->class_entity->start_datetime->format('D j M') ?></div>
                                <small class="text-muted"><?= $booking->class_entity->start_datetime->format('g:ia') ?></small>
                            <?php else: ?>
                                <span class="text-muted"><?= $booking->booking_date ? $booking->booking_date->format('j M Y') : '-' ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $this->Badge->status($booking->booking_status) ?></td>
                        <td>
                            <?php if ($hasPaidRecord): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= $this->Url->build(['action' => 'view', $booking->booking_id]) ?>" class="btn btn-outline-primary">View</a>
                                <a href="<?= $this->Url->build(['action' => 'edit', $booking->booking_id]) ?>" class="btn btn-outline-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-center">
        <ul class="pagination mb-0">
            <?= $this->Paginator->prev('‹ Previous') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next ›') ?>
        </ul>
    </div>
</div>
