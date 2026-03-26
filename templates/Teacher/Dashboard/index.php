<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 * @var iterable<\App\Model\Entity\ClassEntity> $classes
 */
$this->assign('title', 'Teacher Dashboard');
?>
<div class="stats-row">
    <div class="stat-card enquiries">
        <div class="stat-label">My Classes</div>
        <div class="stat-value"><?= h((string)$classCount) ?></div>
    </div>
    <div class="stat-card new-messages">
        <div class="stat-label">Upcoming</div>
        <div class="stat-value"><?= h((string)$upcomingClasses) ?></div>
    </div>
    <div class="stat-card replied">
        <div class="stat-label">Students</div>
        <div class="stat-value"><?= h((string)$studentCount) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Attendance Marked</div>
        <div class="stat-value"><?= h((string)$attendanceMarked) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= h($teacher->teacher_name) ?>'s Schedule</h3>
    </div>
    <div class="card-body">
        <?php if ($classes->isEmpty()): ?>
            <div class="empty-state">
                <div class="icon">&#x1F4C5;</div>
                <p>No classes assigned yet.</p>
            </div>
        <?php else: ?>
            <div class="portal-class-list">
                <?php foreach ($classes as $class): ?>
                    <section class="portal-class-card">
                        <div class="portal-class-card__header">
                            <div>
                                <p class="portal-class-card__eyebrow"><?= h($class->class_code) ?></p>
                                <h3><?= $class->course ? h($class->course->course_name) : 'Class' ?></h3>
                            </div>
                            <span class="badge badge-<?= h($class->class_status) ?>">
                                <?= ucfirst(h($class->class_status)) ?>
                            </span>
                        </div>

                        <div class="portal-class-card__meta">
                            <span><?= $class->start_datetime ? $class->start_datetime->format('D j M, g:ia') : '-' ?></span>
                            <span><?= h($class->location) ?></span>
                            <span><?= count($class->bookings) ?> booked</span>
                        </div>

                        <?php if (empty($class->bookings)): ?>
                            <p class="portal-muted">No students booked into this class yet.</p>
                        <?php else: ?>
                            <div class="portal-attendance-list">
                                <?php foreach ($class->bookings as $booking): ?>
                                    <article class="portal-attendance-card">
                                        <div class="portal-attendance-card__student">
                                            <strong><?= $booking->student ? h($booking->student->student_name) : 'Student' ?></strong>
                                            <span>
                                                Current status:
                                                <?= $booking->attendance_record ? ucfirst(h($booking->attendance_record->attendance_status)) : 'Not marked' ?>
                                            </span>
                                        </div>
                                        <?= $this->Form->create(null, [
                                            'url' => ['prefix' => 'Teacher', 'controller' => 'Dashboard', 'action' => 'markAttendance', $booking->booking_id],
                                            'class' => 'attendance-form',
                                        ]) ?>
                                            <div class="attendance-form__fields">
                                                <?= $this->Form->select('attendance_status', [
                                                    'present' => 'Present',
                                                    'late' => 'Late',
                                                    'absent' => 'Absent',
                                                    'excused' => 'Excused',
                                                ], [
                                                    'value' => $booking->attendance_record?->attendance_status ?? 'present',
                                                    'label' => false,
                                                ]) ?>
                                                <?= $this->Form->text('attendance_notes', [
                                                    'value' => $booking->attendance_record?->attendance_notes,
                                                    'placeholder' => 'Attendance note',
                                                    'label' => false,
                                                ]) ?>
                                            </div>
                                            <?= $this->Form->button('Save Attendance', ['class' => 'btn btn-primary btn-sm']) ?>
                                        <?= $this->Form->end() ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
