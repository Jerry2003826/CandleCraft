<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $records
 * @var array $stats
 * @var string|null $statusFilter
 */
$this->assign('title', 'Attendance Records');
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Total Records</h3>
                <div class="admin-stat-icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)$stats['total']) ?></div>
            <div class="admin-stat-trend neutral">All time</div>
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
                <h3 class="admin-stat-title">Absent</h3>
                <div class="admin-stat-icon"><i class="bi bi-x-circle"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)$stats['absent']) ?></div>
            <div class="admin-stat-trend down">Needs attention</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Late</h3>
                <div class="admin-stat-icon"><i class="bi bi-clock-history"></i></div>
            </div>
            <div class="admin-stat-value"><?= h((string)$stats['late']) ?></div>
            <div class="admin-stat-trend neutral">Tracked</div>
        </div>
    </div>
</div>

<div class="admin-page-header">
    <div class="admin-tabs flex-wrap">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'present']]) ?>" class="admin-tab <?= $statusFilter === 'present' ? 'active' : '' ?>">Present</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'absent']]) ?>" class="admin-tab <?= $statusFilter === 'absent' ? 'active' : '' ?>">Absent</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'late']]) ?>" class="admin-tab <?= $statusFilter === 'late' ? 'active' : '' ?>">Late</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'excused']]) ?>" class="admin-tab <?= $statusFilter === 'excused' ? 'active' : '' ?>">Excused</a>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No attendance records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                    <tr>
                        <td>
                            <p class="admin-table-primary-text"><?= h($record->booking && $record->booking->student ? $record->booking->student->student_name : '-') ?></p>
                        </td>
                        <td>
                            <?php if ($record->booking && $record->booking->class_entity): ?>
                                <a href="<?= $this->Url->build(['action' => 'byClass', $record->booking->class_entity->class_id]) ?>" class="admin-table-primary-text text-decoration-none" style="color: var(--admin-brand-icon);">
                                    <?= h($record->booking->class_entity->class_code) ?>
                                </a>
                            <?php else: ?>
                                <p class="admin-table-secondary-text">-</p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <p class="admin-table-secondary-text"><?= h($record->booking && $record->booking->class_entity && $record->booking->class_entity->course ? $record->booking->class_entity->course->course_name : '-') ?></p>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'admin-badge-neutral';
                                if ($record->attendance_status === 'present') $statusClass = 'admin-badge-success';
                                if ($record->attendance_status === 'absent') $statusClass = 'admin-badge-danger';
                                if ($record->attendance_status === 'late') $statusClass = 'admin-badge-warning';
                            ?>
                            <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($record->attendance_status)) ?></span>
                        </td>
                        <td>
                            <p class="admin-table-secondary-text"><?= h($record->attendance_notes ?? '-') ?></p>
                        </td>
                        <td>
                            <div class="admin-action-links justify-content-end">
                                <?php if ($record->booking && $record->booking->class_entity): ?>
                                    <a href="<?= $this->Url->build(['action' => 'byClass', $record->booking->class_entity->class_id]) ?>" class="admin-action-link view" title="View Class">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
