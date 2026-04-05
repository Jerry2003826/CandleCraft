<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $records
 * @var array $stats
 * @var string|null $statusFilter
 */
$this->assign('title', 'Attendance Records');
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-primary text-center p-3">
            <div class="stat-label">Total Records</div>
            <div class="stat-value"><?= h((string)$stats['total']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-success text-center p-3">
            <div class="stat-label">Present</div>
            <div class="stat-value"><?= h((string)$stats['present']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-danger text-center p-3">
            <div class="stat-label">Absent</div>
            <div class="stat-value"><?= h((string)$stats['absent']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-warning text-center p-3">
            <div class="stat-label">Late</div>
            <div class="stat-value"><?= h((string)$stats['late']) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Attendance Records</h5>
    </div>
    <div class="px-3 pt-3">
        <div class="btn-group btn-group-sm" role="group">
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-primary <?= !$statusFilter ? 'active' : '' ?>">All</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'present']]) ?>" class="btn btn-outline-primary <?= $statusFilter === 'present' ? 'active' : '' ?>">Present</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'absent']]) ?>" class="btn btn-outline-primary <?= $statusFilter === 'absent' ? 'active' : '' ?>">Absent</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'late']]) ?>" class="btn btn-outline-primary <?= $statusFilter === 'late' ? 'active' : '' ?>">Late</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'excused']]) ?>" class="btn btn-outline-primary <?= $statusFilter === 'excused' ? 'active' : '' ?>">Excused</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Student</th><th>Class</th><th>Course</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if ($records->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No attendance records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= h($record->booking && $record->booking->student ? $record->booking->student->student_name : '-') ?></td>
                        <td>
                            <?php if ($record->booking && $record->booking->class_entity): ?>
                                <a href="<?= $this->Url->build(['action' => 'byClass', $record->booking->class_entity->class_id]) ?>"><?= h($record->booking->class_entity->class_code) ?></a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td><?= h($record->booking && $record->booking->class_entity && $record->booking->class_entity->course ? $record->booking->class_entity->course->course_name : '-') ?></td>
                        <td>
                            <span class="badge <?= $record->attendance_status === 'present' ? 'bg-success' : ($record->attendance_status === 'absent' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                <?= ucfirst(h($record->attendance_status)) ?>
                            </span>
                        </td>
                        <td><?= h($record->attendance_notes ?? '-') ?></td>
                        <td>
                            <?php if ($record->booking && $record->booking->class_entity): ?>
                                <a href="<?= $this->Url->build(['action' => 'byClass', $record->booking->class_entity->class_id]) ?>" class="btn btn-sm btn-outline-primary">View Class</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
