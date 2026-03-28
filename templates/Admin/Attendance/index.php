<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $records
 * @var array $stats
 * @var string|null $statusFilter
 */
$this->assign('title', 'Attendance Records');
?>

<div class="stats-row">
    <div class="stat-card enquiries">
        <div class="stat-label">Total Records</div>
        <div class="stat-value"><?= h((string)$stats['total']) ?></div>
    </div>
    <div class="stat-card new-messages">
        <div class="stat-label">Present</div>
        <div class="stat-value"><?= h((string)$stats['present']) ?></div>
    </div>
    <div class="stat-card replied">
        <div class="stat-label">Absent</div>
        <div class="stat-value"><?= h((string)$stats['absent']) ?></div>
    </div>
    <div class="stat-card enquiries" style="border-left-color: #e67e22;">
        <div class="stat-label">Late</div>
        <div class="stat-value"><?= h((string)$stats['late']) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Attendance Records</h3>
    </div>

    <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
        <div style="display: flex; gap: 8px;">
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : '' ?>">All</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'present']]) ?>" class="btn btn-sm <?= $statusFilter === 'present' ? 'btn-primary' : '' ?>">Present</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'absent']]) ?>" class="btn btn-sm <?= $statusFilter === 'absent' ? 'btn-primary' : '' ?>">Absent</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'late']]) ?>" class="btn btn-sm <?= $statusFilter === 'late' ? 'btn-primary' : '' ?>">Late</a>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'excused']]) ?>" class="btn btn-sm <?= $statusFilter === 'excused' ? 'btn-primary' : '' ?>">Excused</a>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Course</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($records->isEmpty()): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">No attendance records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= h($record->booking && $record->booking->student ? $record->booking->student->student_name : '-') ?></td>
                        <td>
                            <?php if ($record->booking && $record->booking->class_entity): ?>
                                <a href="<?= $this->Url->build(['action' => 'byClass', $record->booking->class_entity->class_id]) ?>">
                                    <?= h($record->booking->class_entity->class_code) ?>
                                </a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td><?= h($record->booking && $record->booking->class_entity && $record->booking->class_entity->course ? $record->booking->class_entity->course->course_name : '-') ?></td>
                        <td>
                            <span class="badge badge-<?= h(
                                $record->attendance_status === 'present' ? 'confirmed' :
                                ($record->attendance_status === 'absent' ? 'cancelled' : 'pending')
                            ) ?>">
                                <?= ucfirst(h($record->attendance_status)) ?>
                            </span>
                        </td>
                        <td><?= h($record->attendance_notes ?? '-') ?></td>
                        <td>
                            <?php if ($record->booking && $record->booking->class_entity): ?>
                                <a href="<?= $this->Url->build(['action' => 'byClass', $record->booking->class_entity->class_id]) ?>" class="btn btn-sm btn-primary">View Class</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
