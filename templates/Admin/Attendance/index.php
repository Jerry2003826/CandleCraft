<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $classes
 * @var array $stats
 * @var string $statusFilter
 * @var string $searchTerm
 */
$this->assign('title', 'Attendance Records');

$filterUrl = function (?string $status = null) use ($searchTerm): array {
    $query = [];
    if ($status) {
        $query['status'] = $status;
    }
    if ($searchTerm !== '') {
        $query['q'] = $searchTerm;
    }

    return ['action' => 'index', '?' => $query];
};

$statusBadge = static function (string $status): string {
    return match ($status) {
        'present' => 'admin-badge-success',
        'absent' => 'admin-badge-danger',
        'late' => 'admin-badge-warning',
        default => 'admin-badge-neutral',
    };
};
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Classes</h3>
                <div class="admin-stat-icon"><i class="bi bi-collection"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)$stats['totalClasses']) ?></div>
            <div class="admin-stat-trend neutral">Matching view</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Marked</h3>
                <div class="admin-stat-icon"><i class="bi bi-file-earmark-check"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)$stats['totalRecords']) ?></div>
            <div class="admin-stat-trend neutral">Attendance records</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Present</h3>
                <div class="admin-stat-icon"><i class="bi bi-check-circle"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)$stats['present']) ?></div>
            <div class="admin-stat-trend up">Good</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Needs Review</h3>
                <div class="admin-stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)($stats['absent'] + $stats['late'])) ?></div>
            <div class="admin-stat-trend down">Absent or late</div>
        </div>
    </div>
</div>

<div class="admin-page-header flex-wrap gap-3 mb-4">
    <div>
        <h2 class="admin-form-title mb-1" style="font-size: 20px;">Attendance by Class</h2>
        <p class="admin-table-secondary-text mb-0">Search for a class, then open it to review each student's attendance.</p>
    </div>

    <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" role="search" class="d-flex gap-2 flex-wrap align-items-center">
        <?php if ($statusFilter !== ''): ?>
            <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
        <?php endif; ?>
        <div class="admin-search" style="min-width: 280px;">
            <i class="bi bi-search" aria-hidden="true"></i>
            <label for="attendance-class-search" class="visually-hidden">Search attendance classes</label>
            <input
                type="text"
                id="attendance-class-search"
                name="q"
                value="<?= h($searchTerm) ?>"
                placeholder="Search class, course, teacher, or room"
                aria-label="Search attendance classes"
            >
        </div>
        <button type="submit" class="admin-btn-primary"><i class="bi bi-search me-1"></i> Search</button>
        <?php if ($searchTerm !== '' || $statusFilter !== ''): ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-page-header">
    <div class="admin-tabs flex-wrap">
        <a href="<?= $this->Url->build($filterUrl()) ?>" class="admin-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build($filterUrl('present')) ?>" class="admin-tab <?= $statusFilter === 'present' ? 'active' : '' ?>">Present</a>
        <a href="<?= $this->Url->build($filterUrl('absent')) ?>" class="admin-tab <?= $statusFilter === 'absent' ? 'active' : '' ?>">Absent</a>
        <a href="<?= $this->Url->build($filterUrl('late')) ?>" class="admin-tab <?= $statusFilter === 'late' ? 'active' : '' ?>">Late</a>
        <a href="<?= $this->Url->build($filterUrl('excused')) ?>" class="admin-tab <?= $statusFilter === 'excused' ? 'active' : '' ?>">Excused</a>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Schedule</th>
                    <th>Attendance</th>
                    <th>Status Mix</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($classes->isEmpty()): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No matching classes found.</td></tr>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                        <?php $summary = $class->attendance_summary ?? []; ?>
                        <tr>
                            <td>
                                <a href="<?= $this->Url->build(['action' => 'byClass', $class->class_id]) ?>" class="admin-table-primary-text text-decoration-none" style="color: var(--admin-brand-icon);">
                                    <?= h($class->class_code) ?>
                                </a>
                                <p class="admin-table-secondary-text mb-0"><?= h((string)($class->location ?? '-')) ?></p>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text"><?= h($class->course ? $class->course->course_name : '-') ?></p>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text"><?= h($class->teacher ? $class->teacher->teacher_name : '-') ?></p>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text">
                                    <?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?>
                                </p>
                            </td>
                            <td>
                                <p class="admin-table-primary-text mb-0">
                                    <?= h((string)($summary['marked'] ?? 0)) ?> / <?= h((string)($summary['bookings'] ?? 0)) ?> marked
                                </p>
                                <?php if (($summary['unmarked'] ?? 0) > 0): ?>
                                    <p class="admin-table-secondary-text mb-0"><?= h((string)$summary['unmarked']) ?> still unmarked</p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php foreach (['present', 'absent', 'late', 'excused'] as $status): ?>
                                        <?php if (($summary[$status] ?? 0) > 0): ?>
                                            <span class="admin-badge <?= $statusBadge($status) ?>">
                                                <?= ucfirst($status) ?> <?= h((string)$summary[$status]) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (($summary['marked'] ?? 0) === 0): ?>
                                        <span class="admin-badge admin-badge-neutral">Not marked</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="admin-action-links justify-content-end">
                                    <a href="<?= $this->Url->build(['action' => 'byClass', $class->class_id]) ?>" class="admin-action-link view" title="View Class">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
