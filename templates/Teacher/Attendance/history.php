<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $records
 * @var string|null $statusFilter
 */
$this->assign('title', 'Attendance History');
?>

<div class="card">
    <div class="card-header">
        <h3>Attendance History</h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back</a>
    </div>

    <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <label style="font-weight: 500;">Filter:</label>
            <a href="<?= $this->Url->build(['action' => 'history']) ?>" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : '' ?>">All</a>
            <a href="<?= $this->Url->build(['action' => 'history', '?' => ['status' => 'present']]) ?>" class="btn btn-sm <?= $statusFilter === 'present' ? 'btn-primary' : '' ?>">Present</a>
            <a href="<?= $this->Url->build(['action' => 'history', '?' => ['status' => 'absent']]) ?>" class="btn btn-sm <?= $statusFilter === 'absent' ? 'btn-primary' : '' ?>">Absent</a>
            <a href="<?= $this->Url->build(['action' => 'history', '?' => ['status' => 'late']]) ?>" class="btn btn-sm <?= $statusFilter === 'late' ? 'btn-primary' : '' ?>">Late</a>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Course</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($records->isEmpty()): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">No attendance records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $booking): ?>
                    <?php if ($booking->attendance_record): ?>
                        <tr>
                            <td><?= h($booking->student ? $booking->student->student_name : '-') ?></td>
                            <td><?= h($booking->class_entity ? $booking->class_entity->class_code : '-') ?></td>
                            <td><?= h($booking->class_entity && $booking->class_entity->course ? $booking->class_entity->course->course_name : '-') ?></td>
                            <td>
                                <span class="badge badge-<?= h(
                                    $booking->attendance_record->attendance_status === 'present' ? 'confirmed' :
                                    ($booking->attendance_record->attendance_status === 'absent' ? 'cancelled' : 'pending')
                                ) ?>">
                                    <?= ucfirst(h($booking->attendance_record->attendance_status)) ?>
                                </span>
                            </td>
                            <td><?= h($booking->attendance_record->attendance_notes ?? '-') ?></td>
                            <td><?= h($booking->attendance_record->attendance_date ?? '-') ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
