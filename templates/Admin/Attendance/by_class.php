<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var iterable $bookings
 */
$this->assign('title', 'Attendance - ' . h($class->class_code));
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Attendance: <?= h($class->class_code) ?></h5>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary">&larr; All Records</a>
    </div>
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-md-4">
                <span class="portal-status-row__label">Course</span>
                <div><strong><?= h($class->course ? $class->course->course_name : '-') ?></strong></div>
            </div>
            <div class="col-md-4">
                <span class="portal-status-row__label">Teacher</span>
                <div><strong><?= h($class->teacher ? $class->teacher->teacher_name : '-') ?></strong></div>
            </div>
            <div class="col-md-4">
                <span class="portal-status-row__label">Schedule</span>
                <div><strong><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></strong></div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Student</th><th>Status</th><th>Notes</th><th>Recorded</th></tr></thead>
            <tbody>
                <?php if ($bookings->isEmpty()): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No students enrolled.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= h($booking->student ? $booking->student->student_name : '-') ?></td>
                        <td>
                            <?php if ($booking->attendance_record): ?>
                                <span class="badge <?= $booking->attendance_record->attendance_status === 'present' ? 'bg-success' : ($booking->attendance_record->attendance_status === 'absent' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= ucfirst(h($booking->attendance_record->attendance_status)) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Not Marked</span>
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
</div>
