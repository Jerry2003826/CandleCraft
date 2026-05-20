<?php
/**
 * @var \App\View\AppView $this
 * @var bool $bookingAccessEnabled
 * @var int|null $declaredAge
 * @var int $bookingCount
 * @var int $upcomingCount
 * @var int $presentCount
 * @var array $recentBookings
 */
$this->assign('title', 'Customer Portal');

$identity = $this->request->getAttribute('identity');
$firstName = 'Customer';
if ($identity) {
    $username = (string)$identity->get('username');
    if ($username !== '') {
        $parts = explode(' ', $username);
        $firstName = $parts[0];
    }
}

$nextClass = $recentBookings[0] ?? null;
$otherBookings = array_slice($recentBookings ?? [], 1, 3);
?>

<div class="admin-form-card mb-4 welcome-banner" style="border: none; padding: 32px; max-width: 100%;">
    <h2 class="welcome-title" style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; margin: 0 0 8px 0;">
        Welcome back, <?= h($firstName) ?>!
    </h2>
</div>

<div class="row g-4 align-items-stretch"> <div class="col-lg-7 d-flex flex-column">
        <h3 class="admin-form-title mb-3" style="font-size: 20px;">Next Upcoming Class</h3>

        <?php if (!$nextClass): ?>
            <div class="admin-form-card text-center py-5 flex-grow-1 d-flex flex-column justify-content-center border-0 shadow-sm" style="border-radius: 16px;">
                <i class="bi bi-calendar-event text-muted" style="font-size: 48px;"></i>
                <p class="mt-3 text-muted">No upcoming classes booked yet.</p>
            </div>
        <?php else: ?>
            <div class="admin-form-card flex-grow-1 d-flex flex-column border-0 shadow-sm" style="padding: 28px; border-radius: 16px;">
                <div class="d-flex justify-content-between align-items-start mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
                    <div>
                        <h3 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: var(--admin-text-primary); margin: 0 0 8px 0;">
                            <?= h($nextClass->class_entity?->course?->course_name ?? '-') ?>
                        </h3>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="admin-badge admin-badge-info"><?= h($nextClass->class_entity?->class_code ?? '-') ?></span>
                            <span class="admin-badge admin-badge-success">Attendance: <?= h(ucfirst((string)$nextClass->attendance_record?->attendance_status ?? 'Present')) ?></span>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.05em;">Date</small>
                        <div class="fw-bold" style="font-size: 15px;"><?= $nextClass->class_entity?->start_datetime?->format('j M Y') ?? '-' ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.05em;">Time</small>
                        <div class="fw-bold" style="font-size: 15px;"><?= $nextClass->class_entity?->start_datetime?->format('g:i A') ?? '-' ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.05em;">Location</small>
                        <div class="fw-bold" style="font-size: 15px;"><?= h($nextClass->class_entity?->location ?? 'Room B') ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.05em;">Teacher</small>
                        <div class="fw-bold" style="font-size: 15px;"><?= h($nextClass->class_entity?->teacher?->teacher_name ?? 'James Knit') ?></div>
                    </div>
                </div>

                <div class="mt-auto d-flex justify-content-end">
                    <a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'index']) ?>" class="admin-btn-secondary" style="padding: 6px 16px; font-size: 13px; text-decoration: none; border-radius: 8px;">My Schedule</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-4">
        <div>
            <h3 class="admin-form-title mb-3" style="font-size: 20px;">Portal Overview</h3>
            <div class="row g-3">
                <div class="col-6"><div class="admin-form-card p-3 border-0 shadow-sm" style="border-radius: 12px;">
                    <small class="text-muted d-block mb-1">Booked</small>
                    <span class="h2 fw-bold mb-0"><?= h((string)$bookingCount) ?></span>
                </div></div>
                <div class="col-6"><div class="admin-form-card p-3 border-0 shadow-sm" style="border-radius: 12px;">
                    <small class="text-muted d-block mb-1">Upcoming</small>
                    <span class="h2 fw-bold mb-0"><?= h((string)$upcomingCount) ?></span>
                </div></div>
                <div class="col-12"><div class="admin-form-card p-3 border-0 shadow-sm" style="border-radius: 12px;">
                    <small class="text-muted d-block mb-1">Present Marks</small>
                    <span class="h2 fw-bold mb-0"><?= h((string)$presentCount) ?></span>
                </div></div>
            </div>
        </div>

        <div>
            <h3 class="admin-form-title mb-3" style="font-size: 20px;">Recent Classes</h3>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($otherBookings as $booking): ?>
                    <div class="admin-form-card p-3 border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="fw-bold" style="font-size: 14px;"><?= h($booking->class_entity?->course?->course_name ?? 'Pottery') ?></div>
                        <small class="text-muted"><?= $booking->class_entity?->start_datetime?->format('j M, g:ia') ?? '22 Apr' ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
