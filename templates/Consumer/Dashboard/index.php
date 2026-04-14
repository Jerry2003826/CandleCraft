<?php
/**
 * @var \App\View\AppView $this
 * @var bool $isAdult
 * @var string $userRole
 * @var int $bookingCount
 * @var int $upcomingCount
 * @var int $presentCount
 * @var array $recentBookings
 * @var array $children
 */
$this->assign('title', 'Dashboard');

// Determine the user's first name for the welcome banner
$identity = $this->request->getAttribute('identity');
$firstName = 'Student';
if ($identity) {
    $username = $identity->get('username');
    if ($username) {
        $parts = explode(' ', $username);
        $firstName = $parts[0];
    }
}
?>

<div class="admin-form-card mb-4 welcome-banner" style="border: none; padding: 32px; max-width: 100%;">
    <h2 class="welcome-title" style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; margin: 0 0 8px 0;">
        Welcome back, <?= h($firstName) ?>!
    </h2>
    <p class="welcome-subtitle" style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; margin: 0;">
        You have <?= $upcomingCount ?> upcoming class<?= $upcomingCount !== 1 ? 'es' : '' ?> this week. Keep up the great work!
    </p>
</div>

<?php if (!$isAdult): ?>
    <div class="alert alert-info d-flex align-items-center mb-4" style="border-radius: 12px; border: 1px solid var(--admin-card-border);">
        <i class="bi bi-info-circle me-2" style="font-size: 20px;"></i>
        <div style="font-family: 'Inter', sans-serif; font-size: 14px;">You are under 18. You can view your schedule and learning resources. Booking and payment features require an adult account.</div>
    </div>
<?php elseif ($isAdult && !$ageVerifiedByAdmin): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4" style="border-radius: 12px; border: 1px solid var(--admin-card-border);">
        <i class="bi bi-shield-exclamation me-2" style="font-size: 20px;"></i>
        <div style="font-family: 'Inter', sans-serif; font-size: 14px;">
            <strong>Payment not yet enabled.</strong> You can browse courses, create bookings, and access all other features. Payment will be available once an administrator verifies your age.
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <!-- Left Column: Next Upcoming Class -->
    <div class="col-lg-7 d-flex flex-column">
        <h3 class="admin-form-title mb-4">Next Upcoming Class</h3>
        
        <?php if (empty($recentBookings)): ?>
            <div class="admin-form-card text-center py-5 flex-grow-1 d-flex flex-column justify-content-center" style="max-width: 100%;">
                <i class="bi bi-calendar-event" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
                <p class="mt-3" style="color: var(--admin-text-secondary);">No upcoming classes.</p>
                <?php if ($isAdult): ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary mt-2 mx-auto">
                        Browse Courses
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php 
            // Get the first upcoming booking
            $nextClass = $recentBookings[0]; 
            $courseType = strtolower($nextClass->class_entity?->course?->course_type ?? 'default');
            $typeColor = $courseType === 'pottery' ? '#1D4ED8' : ($courseType === 'knitting' ? '#B45309' : '#374151');
            $typeBg = $courseType === 'pottery' ? '#DBEAFE' : ($courseType === 'knitting' ? '#FEF3C7' : '#F3F4F6');
            ?>
            <div class="admin-form-card flex-grow-1 d-flex flex-column" style="padding: 24px; max-width: 100%;">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-4" style="border-bottom: 1px solid var(--admin-card-border);">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background-color: var(--admin-search-bg); display: flex; justify-content: center; align-items: center;">
                            <i class="bi bi-mortarboard" style="font-size: 24px; color: var(--admin-brand-icon);"></i>
                        </div>
                        <div>
                            <h3 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 18px; color: var(--admin-text-primary); margin: 0 0 4px 0;">
                                <?= h($nextClass->class_entity?->course?->course_name ?? $nextClass->class_entity?->class_code ?? '-') ?>
                            </h3>
                            <span class="admin-badge admin-badge-info" style="padding: 2px 8px; font-size: 11px;">
                                <?= ucfirst(h($courseType)) ?>
                            </span>
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
                    <div class="col-12 mt-3">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Location</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                            <?= h($nextClass->class_entity?->location ?? 'TBA') ?>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-auto">
                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']) ?>" class="admin-btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                        View Schedule
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Quick Actions -->
    <div class="col-lg-5 d-flex flex-column">
        <h3 class="admin-form-title mb-4">Quick Actions</h3>
        
        <div class="row g-3 flex-grow-1">
            <div class="col-sm-6 d-flex">
                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-form-card d-flex flex-column align-items-start text-decoration-none flex-grow-1" style="padding: 24px; max-width: 100%; transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg);">
                    <div style="width: 40px; height: 40px; background-color: rgba(59, 130, 246, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <i class="bi bi-calendar-plus" style="font-size: 20px; color: #3B82F6;"></i>
                    </div>
                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 4px;">Book a Class</span>
                    <span style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Browse our schedule</span>
                </a>
            </div>
            <div class="col-sm-6 d-flex">
                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Resources', 'action' => 'index']) ?>" class="admin-form-card d-flex flex-column align-items-start text-decoration-none flex-grow-1" style="padding: 24px; max-width: 100%; transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg);">
                    <div style="width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <i class="bi bi-folder2-open" style="font-size: 20px; color: #F59E0B;"></i>
                    </div>
                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 4px;">View Resources</span>
                    <span style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Access course materials</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Recent Bookings -->
    <div class="col-lg-7 d-flex flex-column">
        <h3 class="admin-form-title mb-4">Recent Bookings</h3>
        <?php $restBookings = array_slice($recentBookings ?? [], 1, 3); ?>
        <?php if (empty($restBookings)): ?>
            <div class="admin-form-card text-center py-4 flex-grow-1 d-flex flex-column justify-content-center" style="max-width: 100%;">
                <p class="mb-0" style="color: var(--admin-text-secondary);">No other recent bookings.</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3 flex-grow-1">
                <?php foreach ($restBookings as $booking): ?>
                    <div style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; transition: border-color 0.2s;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: var(--admin-search-bg); display: flex; justify-content: center; align-items: center;">
                                <i class="bi bi-calendar-event" style="font-size: 18px; color: var(--admin-text-secondary);"></i>
                            </div>
                            <div>
                                <?php if ($userRole === 'parent'): ?>
                                    <div style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary); margin-bottom: 2px;">
                                        <?= h($booking->student?->student_name ?? '-') ?>
                                    </div>
                                <?php endif; ?>
                                <h4 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin: 0 0 2px 0;">
                                    <?= h($booking->class_entity?->course?->course_name ?? '-') ?>
                                </h4>
                                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); display: flex; align-items: center; gap: 8px;">
                                    <span><?= h($booking->class_entity?->class_code ?? '-') ?></span>
                                    <span style="opacity: 0.5;">•</span>
                                    <span><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('j M, g:ia') : '-' ?></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <?php 
                                $statusClass = 'admin-badge-neutral';
                                if ($booking->booking_status === 'Confirmed') $statusClass = 'admin-badge-success';
                                if ($booking->booking_status === 'Pending') $statusClass = 'admin-badge-warning';
                                if ($booking->booking_status === 'Cancelled') $statusClass = 'admin-badge-danger';
                            ?>
                            <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($booking->booking_status)) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Overview & Children -->
    <div class="col-lg-5 d-flex flex-column">
        <h3 class="admin-form-title mb-4">Overview</h3>
        <div class="row g-3">
            <div class="col-sm-6 d-flex">
                <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg); display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h3 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">Booked Classes</h3>
                        <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(59, 130, 246, 0.1); display: flex; justify-content: center; align-items: center;">
                            <i class="bi bi-journal-check" style="font-size: 16px; color: #3B82F6;"></i>
                        </div>
                    </div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                        <?= h((string)$bookingCount) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 d-flex">
                <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg); display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h3 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">Present Marks</h3>
                        <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(16, 185, 129, 0.1); display: flex; justify-content: center; align-items: center;">
                            <i class="bi bi-check2-circle" style="font-size: 16px; color: #10B981;"></i>
                        </div>
                    </div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                        <?= h((string)$presentCount) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($userRole === 'parent' && !empty($children)): ?>
            <h3 class="admin-form-title mt-5 mb-4">My Children</h3>
            <div class="row g-3">
                <?php foreach ($children as $child): ?>
                    <div class="col-12">
                        <div class="admin-form-card" style="padding: 20px; max-width: 100%;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h4 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin: 0;">
                                    <?= h($child->student_name) ?>
                                </h4>
                                <?php 
                                    $statusClass = 'admin-badge-neutral';
                                    if (($child->student_status ?? 'active') === 'active') $statusClass = 'admin-badge-success';
                                    if (($child->student_status ?? 'active') === 'inactive') $statusClass = 'admin-badge-danger';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>">
                                    <?= ucfirst(h($child->student_status ?? 'active')) ?>
                                </span>
                            </div>
                            <div style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                                <i class="bi bi-calendar3 me-1"></i> DOB: <?= $child->date_of_birth ? $child->date_of_birth->format('j M Y') : '-' ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
