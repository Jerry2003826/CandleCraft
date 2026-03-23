<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 * @var iterable<\App\Model\Entity\Booking> $bookings
 */
$this->assign('title', 'Student Dashboard');
?>
<div class="stats-row">
    <div class="stat-card enquiries">
        <div class="stat-label">Booked Classes</div>
        <div class="stat-value"><?= h((string)$bookingCount) ?></div>
    </div>
    <div class="stat-card new-messages">
        <div class="stat-label">Upcoming</div>
        <div class="stat-value"><?= h((string)$upcomingClasses) ?></div>
    </div>
    <div class="stat-card replied">
        <div class="stat-label">Present Marks</div>
        <div class="stat-value"><?= h((string)$presentCount) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= h($student->student_name) ?>'s Class Schedule</h3>
    </div>
    <div class="card-body">
        <?php if ($bookings->isEmpty()): ?>
            <div class="empty-state">
                <div class="icon">&#x1F393;</div>
                <p>No classes are linked to this student yet.</p>
            </div>
        <?php else: ?>
            <div class="portal-class-list">
                <?php foreach ($bookings as $booking): ?>
                    <section class="portal-class-card">
                        <div class="portal-class-card__header">
                            <div>
                                <p class="portal-class-card__eyebrow">
                                    <?= $booking->class_entity ? h($booking->class_entity->class_code) : 'Class' ?>
                                </p>
                                <h3><?= $booking->class_entity && $booking->class_entity->course ? h($booking->class_entity->course->course_name) : 'Class' ?></h3>
                            </div>
                            <span class="badge badge-<?= h($booking->booking_status) ?>">
                                <?= ucfirst(h($booking->booking_status)) ?>
                            </span>
                        </div>

                        <div class="portal-class-card__meta">
                            <span>
                                <?= $booking->class_entity && $booking->class_entity->start_datetime ? $booking->class_entity->start_datetime->format('D j M, g:ia') : '-' ?>
                            </span>
                            <span><?= $booking->class_entity ? h($booking->class_entity->location) : '-' ?></span>
                            <span>
                                Teacher:
                                <?= $booking->class_entity && $booking->class_entity->teacher ? h($booking->class_entity->teacher->teacher_name) : '-' ?>
                            </span>
                        </div>

                        <div class="portal-status-row">
                            <div>
                                <span class="portal-status-row__label">Attendance</span>
                                <strong>
                                    <?= $booking->attendance_record ? ucfirst(h($booking->attendance_record->attendance_status)) : 'Pending' ?>
                                </strong>
                            </div>
                            <div>
                                <span class="portal-status-row__label">Note</span>
                                <strong><?= $booking->attendance_record && $booking->attendance_record->attendance_notes ? h($booking->attendance_record->attendance_notes) : 'No notes yet' ?></strong>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
