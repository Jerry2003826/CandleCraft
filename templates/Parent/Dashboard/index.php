<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ParentEntity $parent
 * @var array $children
 * @var int $bookingCount
 * @var int $upcomingCount
 * @var int $attendanceCount
 * @var int $presentCount
 * @var array $recentBookings
 */
$this->assign('title', 'Parent Dashboard');
?>

<div class="stats-row">
    <div class="stat-card enquiries">
        <div class="stat-label">Children</div>
        <div class="stat-value"><?= count($children) ?></div>
    </div>
    <div class="stat-card new-messages">
        <div class="stat-label">Bookings</div>
        <div class="stat-value"><?= h((string)$bookingCount) ?></div>
    </div>
    <div class="stat-card replied">
        <div class="stat-label">Upcoming</div>
        <div class="stat-value"><?= h((string)$upcomingCount) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Present</div>
        <div class="stat-value"><?= h((string)$presentCount) ?> / <?= h((string)$attendanceCount) ?></div>
    </div>
</div>

<div class="card" id="children">
    <div class="card-header">
        <h3>My Children</h3>
    </div>
    <div class="card-body">
        <?php if (empty($children)): ?>
            <div class="empty-state">
                <div class="icon">&#x1F476;</div>
                <p>No children linked yet. Please contact admin.</p>
            </div>
        <?php else: ?>
            <div class="portal-class-list">
                <?php foreach ($children as $child): ?>
                    <section class="portal-class-card">
                        <div class="portal-class-card__header">
                            <div>
                                <p class="portal-class-card__eyebrow">Student</p>
                                <h3><?= h($child->student_name) ?></h3>
                            </div>
                            <span class="badge badge-<?= h($child->student_status ?? 'active') ?>">
                                <?= ucfirst(h($child->student_status ?? 'active')) ?>
                            </span>
                        </div>
                        <div class="portal-class-card__meta">
                            <span>DOB: <?= $child->date_of_birth ? $child->date_of_birth->format('j M Y') : '-' ?></span>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Bookings</h3>
        <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="btn btn-sm">View All</a>
    </div>
    <?php if (empty($recentBookings)): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4C5;</div>
            <p>No bookings yet.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Schedule</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBookings as $booking): ?>
                    <tr>
                        <td><?= h($booking->student?->student_name ?? '-') ?></td>
                        <td><?= h($booking->class_entity?->course?->course_name ?? $booking->class_entity?->class_code ?? '-') ?></td>
                        <td><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                        <td><span class="badge badge-<?= h($booking->booking_status) ?>"><?= ucfirst(h($booking->booking_status)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
