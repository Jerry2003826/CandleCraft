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

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-primary text-center p-3">
            <span class="stat-icon"><i class="bi bi-person-hearts"></i></span>
            <div class="stat-label">Children</div>
            <div class="stat-value"><?= count($children) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-success text-center p-3">
            <span class="stat-icon"><i class="bi bi-calendar-check"></i></span>
            <div class="stat-label">Bookings</div>
            <div class="stat-value"><?= h((string)$bookingCount) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-info text-center p-3">
            <span class="stat-icon"><i class="bi bi-clock"></i></span>
            <div class="stat-label">Upcoming</div>
            <div class="stat-value"><?= h((string)$upcomingCount) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-warning text-center p-3">
            <span class="stat-icon"><i class="bi bi-patch-check"></i></span>
            <div class="stat-label">Present</div>
            <div class="stat-value"><?= h((string)$presentCount) ?> / <?= h((string)$attendanceCount) ?></div>
        </div>
    </div>
</div>

<!-- Middle: Quick Actions + Recent Bookings -->
<div class="row g-4 mb-4">
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body p-0">
             <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index']) ?>" 
                class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none border-bottom" style="color: var(--rd-text-title, #2f2219);">
                <i class="bi bi-plus-circle" style="color: var(--rd-primary, #d29a58); font-size: 1.1rem;"></i>
                <span style="font-weight: 500;">New Booking</span>
                <i class="bi bi-chevron-right ms-auto" style="opacity: 0.3;"></i>
            </a>
            <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']) ?>" 
                class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none border-bottom" style="color: var(--rd-text-title, #2f2219);">
                <i class="bi bi-calendar-event" style="color: var(--rd-primary, #d29a58); font-size: 1.1rem;"></i>
                <span style="font-weight: 500;">View Schedule</span>
                <i class="bi bi-chevron-right ms-auto" style="opacity: 0.3;"></i>
            </a>
            <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'index']) ?>" 
                class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none" style="color: var(--rd-text-title, #2f2219);">
                <i class="bi bi-credit-card" style="color: var(--rd-primary, #d29a58); font-size: 1.1rem;"></i>
                <span style="font-weight: 500;">Payment Portal</span>
                <i class="bi bi-chevron-right ms-auto" style="opacity: 0.3;"></i>
             </a>
        </div>
    </div>
</div>

    <!-- Recent Bookings -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Bookings</h5>
                <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index']) ?>"
                   class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($recentBookings)): ?>
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-calendar-event" style="font-size: 48px;"></i>
                    <p class="mt-3">No bookings yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 parent-bookings-table">
                        <thead>
                            <tr><th>Student</th><th>Course</th><th>Schedule</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><?= h($booking->student?->student_name ?? '-') ?></td>
                                    <td><?= h($booking->class_entity?->course?->course_name ?? '-') ?></td>
                                    <td><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                                    <td><span class="badge badge-<?= h($booking->booking_status) ?>"><?= ucfirst(h($booking->booking_status)) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- My Children (Bottom) -->
<div class="card" id="children">
    <div class="card-header">
        <h5 class="mb-0">My Children</h5>
    </div>
    <div class="card-body">
        <?php if (empty($children)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-hearts" style="font-size: 48px;"></i>
                <p class="mt-3">No children linked yet. Please contact admin.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($children as $child): ?>
                    <div class="col-md-6">
                        <div class="portal-class-card">
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
                                <span><i class="bi bi-calendar3 me-1"></i>DOB: <?= $child->date_of_birth ? $child->date_of_birth->format('j M Y') : '-' ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>