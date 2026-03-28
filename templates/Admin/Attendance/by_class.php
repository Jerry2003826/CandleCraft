<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var iterable $bookings
 */
$this->assign('title', 'Attendance - ' . h($class->class_code));
?>

<div class="card">
    <div class="card-header">
        <h3>Attendance: <?= h($class->class_code) ?></h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; All Records</a>
    </div>

    <div class="portal-status-row" style="padding: 16px; border-bottom: 1px solid #eee;">
        <div>
            <span class="portal-status-row__label">Course</span>
            <strong><?= h($class->course ? $class->course->course_name : '-') ?></strong>
        </div>
        <div>
            <span class="portal-status-row__label">Teacher</span>
            <strong><?= h($class->teacher ? $class->teacher->teacher_name : '-') ?></strong>
        </div>
        <div>
            <span class="portal-status-row__label">Schedule</span>
            <strong><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></strong>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Recorded</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings->isEmpty()): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 30px; color: #7f8c8d;">No students enrolled.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= h($booking->student ? $booking->student->student_name : '-') ?></td>
                        <td>
                            <?php if ($booking->attendance_record): ?>
                                <span class="badge badge-<?= h(
                                    $booking->attendance_record->attendance_status === 'present' ? 'confirmed' :
                                    ($booking->attendance_record->attendance_status === 'absent' ? 'cancelled' : 'pending')
                                ) ?>">
                                    <?= ucfirst(h($booking->attendance_record->attendance_status)) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-pending">Not Marked</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($booking->attendance_record ? ($booking->attendance_record->attendance_notes ?? '-') : '-') ?></td>
                        <td><?= h($booking->attendance_record ? ($booking->attendance_record->attendance_date ?? '-') : '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
