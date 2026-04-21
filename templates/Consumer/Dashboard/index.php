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
    <p class="welcome-subtitle" style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; margin: 0;">
        This portal gives you quick access to your schedule, learning resources, class bookings, and payment history.
    </p>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 24px; border: 1px solid <?= $bookingAccessEnabled ? 'rgba(16, 185, 129, 0.25)' : 'rgba(245, 158, 11, 0.25)' ?>;">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
            <div style="font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: <?= $bookingAccessEnabled ? '#10B981' : '#F59E0B' ?>; margin-bottom: 8px;">
                Account Status
            </div>
            <h3 class="admin-form-title" style="font-size: 22px; margin-bottom: 8px;">
                <?= $bookingAccessEnabled ? 'Verified for booking and payment' : 'Pending adult verification' ?>
            </h3>
            <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">
                <?php if ($bookingAccessEnabled): ?>
                    Your customer portal is fully unlocked. You can book classes, pay online, and download receipts.
                <?php else: ?>
                    You can browse classes, view your schedule, and open learning resources now. Booking and payment will unlock after an administrator confirms you are 18 or older.
                    <?php if ($declaredAge !== null): ?>
                        Declared age: <?= h((string)$declaredAge) ?>.
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-start">
            <span class="admin-badge <?= $bookingAccessEnabled ? 'admin-badge-success' : 'admin-badge-warning' ?>" style="padding: 8px 14px; font-size: 13px;">
                <?= $bookingAccessEnabled ? 'Verified' : 'Pending review' ?>
            </span>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-6 col-xl-3 d-flex">
        <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="admin-form-card d-flex flex-column text-decoration-none flex-grow-1" style="padding: 24px; max-width: 100%;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(59, 130, 246, 0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <i class="bi bi-calendar-check" style="font-size: 20px; color: #3B82F6;"></i>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 6px;">My Schedule</div>
            <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">See your upcoming classes, attendance records, and reminder status.</div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3 d-flex">
        <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Resources', 'action' => 'index']) ?>" class="admin-form-card d-flex flex-column text-decoration-none flex-grow-1" style="padding: 24px; max-width: 100%;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(245, 158, 11, 0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <i class="bi bi-folder2-open" style="font-size: 20px; color: #F59E0B;"></i>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 6px;">Learning Resources</div>
            <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Download learning materials shared for your current class bookings.</div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3 d-flex">
        <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-form-card d-flex flex-column text-decoration-none flex-grow-1" style="padding: 24px; max-width: 100%;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <i class="bi bi-journal-plus" style="font-size: 20px; color: #10B981;"></i>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 6px;">Book a Class</div>
            <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                <?= $bookingAccessEnabled ? 'Browse available classes and start a new booking.' : 'You can browse classes now. Booking unlocks after adult verification.' ?>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3 d-flex">
        <?php if ($bookingAccessEnabled): ?>
            <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'index']) ?>" class="admin-form-card d-flex flex-column text-decoration-none flex-grow-1" style="padding: 24px; max-width: 100%;">
                <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(139, 92, 246, 0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <i class="bi bi-credit-card" style="font-size: 20px; color: #8B5CF6;"></i>
                </div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 6px;">Payment Portal</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Review payment history, receipts, and saved billing details.</div>
            </a>
        <?php else: ?>
            <div class="admin-form-card d-flex flex-column flex-grow-1" style="padding: 24px; max-width: 100%; opacity: 0.82;">
                <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(107, 114, 128, 0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <i class="bi bi-lock" style="font-size: 20px; color: #6B7280;"></i>
                </div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 6px;">Payment Portal</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Locked until an administrator verifies your age.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7 d-flex flex-column">
        <h3 class="admin-form-title mb-4">Next Upcoming Class</h3>

        <?php if (!$nextClass): ?>
            <div class="admin-form-card text-center py-5 flex-grow-1 d-flex flex-column justify-content-center" style="max-width: 100%;">
                <i class="bi bi-calendar-event" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
                <p class="mt-3" style="color: var(--admin-text-secondary);">No upcoming classes are booked yet.</p>
            </div>
        <?php else: ?>
            <div class="admin-form-card flex-grow-1 d-flex flex-column" style="padding: 24px; max-width: 100%;">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4 pb-4" style="border-bottom: 1px solid var(--admin-card-border);">
                    <div>
                        <h3 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 20px; color: var(--admin-text-primary); margin: 0 0 8px 0;">
                            <?= h($nextClass->class_entity?->course?->course_name ?? $nextClass->class_entity?->class_code ?? '-') ?>
                        </h3>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="admin-badge admin-badge-info"><?= h($nextClass->class_entity?->class_code ?? '-') ?></span>
                            <span class="admin-badge admin-badge-neutral"><?= h(ucfirst((string)$nextClass->booking_status)) ?></span>
                            <?php if ($nextClass->attendance_record?->attendance_status): ?>
                                <span class="admin-badge admin-badge-success">Attendance: <?= h(ucfirst((string)$nextClass->attendance_record->attendance_status)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4 flex-grow-1">
                    <div class="col-sm-6">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Date</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                            <?= $nextClass->class_entity?->start_datetime ? $nextClass->class_entity->start_datetime->format('j M Y') : '-' ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Time</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                            <?= $nextClass->class_entity?->start_datetime ? $nextClass->class_entity->start_datetime->format('g:i A') : '-' ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Location</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                            <?= h($nextClass->class_entity?->location ?? 'TBA') ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Teacher</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                            <?= h($nextClass->class_entity?->teacher?->teacher_name ?? 'TBA') ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-auto">
                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="admin-btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                        My Schedule
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5 d-flex flex-column gap-4">
        <div>
            <h3 class="admin-form-title mb-4">Portal Overview</h3>
            <div class="row g-3">
                <div class="col-sm-6 d-flex">
                    <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%;">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 16px;">Booked Classes</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                            <?= h((string)$bookingCount) ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 d-flex">
                    <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%;">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 16px;">Upcoming This Week</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                            <?= h((string)$upcomingCount) ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex">
                    <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%;">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 16px;">Present Marks</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                            <?= h((string)$presentCount) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="admin-form-title mb-4">Recent Classes</h3>
            <?php if ($otherBookings === []): ?>
                <div class="admin-form-card text-center py-4" style="max-width: 100%;">
                    <p class="mb-0" style="color: var(--admin-text-secondary);">No other recent class activity yet.</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($otherBookings as $booking): ?>
                        <div class="admin-form-card" style="padding: 18px; max-width: 100%;">
                            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 6px;">
                                <?= h($booking->class_entity?->course?->course_name ?? '-') ?>
                            </div>
                            <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                                <?= h($booking->class_entity?->class_code ?? '-') ?>
                                <span style="opacity: 0.55;">•</span>
                                <?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('j M, g:ia') : '-' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
