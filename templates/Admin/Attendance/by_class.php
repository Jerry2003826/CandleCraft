<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var iterable $bookings
 */
$this->assign('title', 'Attendance - ' . h($class->class_code));
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link mb-0">
        <i class="bi bi-arrow-left"></i> All Records
    </a>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <h2 class="admin-form-title mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border); font-size: 20px;">
        Attendance: <?= h($class->class_code) ?>
    </h2>
    
    <div class="row g-4">
        <div class="col-md-4">
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Course</div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                <?= h($class->course ? $class->course->course_name : '-') ?>
            </div>
        </div>
        <div class="col-md-4">
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Teacher</div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                <?= h($class->teacher ? $class->teacher->teacher_name : '-') ?>
            </div>
        </div>
        <div class="col-md-4">
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Schedule</div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                <?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?>
            </div>
        </div>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="table mb-0">
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
                        <td colspan="4" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center justify-content-center">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background-color: var(--admin-search-bg); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                    <i class="bi bi-people" style="font-size: 20px; color: var(--admin-text-secondary);"></i>
                                </div>
                                <h5 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 4px;">No students enrolled</h5>
                                <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">There are no bookings for this class yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 13px;">
                                    <?= strtoupper(substr(h($booking->student ? $booking->student->student_name : '?'), 0, 1)) ?>
                                </div>
                                <span style="font-family: 'Inter', sans-serif; font-weight: 500; color: var(--admin-text-primary);">
                                    <?= h($booking->student ? $booking->student->student_name : '-') ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php if ($booking->attendance_record): ?>
                                <?php 
                                    $statusClass = 'admin-badge-neutral';
                                    $status = strtolower($booking->attendance_record->attendance_status);
                                    if ($status === 'present') $statusClass = 'admin-badge-success';
                                    if ($status === 'absent') $statusClass = 'admin-badge-danger';
                                    if ($status === 'late') $statusClass = 'admin-badge-warning';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>">
                                    <?= ucfirst(h($booking->attendance_record->attendance_status)) ?>
                                </span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge-neutral">Not Marked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color: var(--admin-text-secondary); font-size: 14px;">
                                <?= h($booking->attendance_record ? ($booking->attendance_record->attendance_notes ?: '-') : '-') ?>
                            </span>
                        </td>
                        <td>
                            <span style="color: var(--admin-text-secondary); font-size: 14px;">
                                <?php 
                                    if ($booking->attendance_record && $booking->attendance_record->attendance_date) {
                                        $date = $booking->attendance_record->attendance_date;
                                        if (is_object($date) && method_exists($date, 'format')) {
                                            echo $date->format('j M Y, g:ia');
                                        } else {
                                            echo h((string)$date);
                                        }
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
