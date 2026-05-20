<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $records
 * @var string|null $statusFilter
 */
$this->assign('title', 'Attendance History');
?>

<?= $this->element('admin_back_link', ['url' => $this->Url->build(['action' => 'index']), 'label' => 'Back to Attendance']) ?>

<div class="admin-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="admin-tabs flex-wrap">
        <a href="<?= $this->Url->build(['action' => 'history']) ?>" class="admin-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'history', '?' => ['status' => 'present']]) ?>" class="admin-tab <?= $statusFilter === 'present' ? 'active' : '' ?>">Present</a>
        <a href="<?= $this->Url->build(['action' => 'history', '?' => ['status' => 'absent']]) ?>" class="admin-tab <?= $statusFilter === 'absent' ? 'active' : '' ?>">Absent</a>
        <a href="<?= $this->Url->build(['action' => 'history', '?' => ['status' => 'late']]) ?>" class="admin-tab <?= $statusFilter === 'late' ? 'active' : '' ?>">Late</a>
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
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records->isEmpty()): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $booking): ?>
                        <?php if ($booking->attendance_record): ?>
                            <tr>
                                <td>
                                    <p class="admin-table-primary-text mb-0"><?= h($booking->student ? $booking->student->student_name : '-') ?></p>
                                </td>
                                <td>
                                    <p class="admin-table-secondary-text mb-0"><?= h($booking->class_entity ? $booking->class_entity->class_code : '-') ?></p>
                                </td>
                                <td>
                                    <p class="admin-table-primary-text mb-0"><?= h($booking->class_entity && $booking->class_entity->course ? $booking->class_entity->course->course_name : '-') ?></p>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'admin-badge-neutral';
                                        $astat = $booking->attendance_record->attendance_status;
                                        if ($astat === 'present') $statusClass = 'admin-badge-success';
                                        if ($astat === 'absent') $statusClass = 'admin-badge-danger';
                                        if ($astat === 'late') $statusClass = 'admin-badge-warning';
                                    ?>
                                    <span class="admin-badge <?= $statusClass ?>">
                                        <?= ucfirst(h($astat)) ?>
                                    </span>
                                </td>
                                <td>
                                    <p class="admin-table-secondary-text mb-0"><?= h($booking->attendance_record->attendance_notes ?? '-') ?></p>
                                </td>
                                <td>
                                    <p class="admin-table-secondary-text mb-0"><?= h($booking->attendance_record->attendance_date ?? '-') ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
