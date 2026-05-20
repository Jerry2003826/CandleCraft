<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 * @var \App\Model\Entity\ClassEntity[] $upcoming
 */
$this->assign('title', 'Teacher Dashboard');

$firstName = 'Teacher';
if ($teacher->teacher_name) {
    $firstName = explode(' ', $teacher->teacher_name)[0];
}

$upcomingCount = count($upcoming);
?>

<!-- Welcome Banner -->
<div class="admin-form-card mb-4 welcome-banner" style="border: none; padding: 32px; max-width: 100%;">
    <h2 class="welcome-title" style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; margin: 0 0 8px 0;">
        Welcome back, <?= h($firstName) ?>!
    </h2>
    <p class="welcome-subtitle" style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; margin: 0;">
        You have <?= $upcomingCount ?> upcoming class<?= $upcomingCount !== 1 ? 'es' : '' ?>. Have a great time teaching!
    </p>
</div>

<!-- Quick Action Tiles -->
<div class="row g-4 mb-5">
    <div class="col-sm-6 col-xl-4 d-flex">
        <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Attendance', 'action' => 'index']) ?>"
           class="admin-form-card flex-grow-1 text-decoration-none d-flex align-items-center gap-4"
           style="padding: 24px; max-width: 100%; border-radius: 12px;">
            <div style="width: 52px; height: 52px; flex-shrink: 0; border-radius: 14px; background-color: rgba(59,130,246,0.1); display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-clipboard-check" style="font-size: 24px; color: #3B82F6;"></i>
            </div>
            <div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 3px;">Manage Attendance</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Mark and review class attendance</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-4 d-flex">
        <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Schedule', 'action' => 'index']) ?>"
           class="admin-form-card flex-grow-1 text-decoration-none d-flex align-items-center gap-4"
           style="padding: 24px; max-width: 100%; border-radius: 12px;">
            <div style="width: 52px; height: 52px; flex-shrink: 0; border-radius: 14px; background-color: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-calendar-range" style="font-size: 24px; color: #F59E0B;"></i>
            </div>
            <div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 3px;">My Schedule</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">View and browse your classes</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-4 d-flex">
        <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Resources', 'action' => 'index']) ?>"
           class="admin-form-card flex-grow-1 text-decoration-none d-flex align-items-center gap-4"
           style="padding: 24px; max-width: 100%; border-radius: 12px;">
            <div style="width: 52px; height: 52px; flex-shrink: 0; border-radius: 14px; background-color: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-folder" style="font-size: 24px; color: #10B981;"></i>
            </div>
            <div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 3px;">Class Resources</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Upload and manage materials</div>
            </div>
        </a>
    </div>
</div>

<!-- Upcoming Classes -->
<h3 class="admin-form-title mb-4">Upcoming Classes</h3>

<?php if (empty($upcoming)): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-calendar-x" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-secondary);">
            No upcoming classes scheduled.
        </p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($upcoming as $class):
            $statusBadge = match ($class->class_status ?? '') {
                'scheduled' => 'admin-badge-info',
                'completed' => 'admin-badge-success',
                'cancelled' => 'admin-badge-danger',
                default     => 'admin-badge-neutral',
            };
        ?>
            <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Schedule', 'action' => 'view', $class->class_id]) ?>"
               class="admin-form-card text-decoration-none d-flex align-items-center gap-4"
               style="padding: 20px 24px; max-width: 100%;">

                <!-- Date block -->
                <div style="min-width: 56px; text-align: center; background-color: rgba(59,130,246,0.08); border-radius: 10px; padding: 10px 14px; flex-shrink: 0;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: #3B82F6; line-height: 1;">
                        <?= $class->start_datetime ? h($class->start_datetime->format('j')) : '-' ?>
                    </div>
                    <div style="font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 600; color: #3B82F6; text-transform: uppercase; letter-spacing: 0.05em;">
                        <?= $class->start_datetime ? h($class->start_datetime->format('M')) : '' ?>
                    </div>
                </div>

                <!-- Class info -->
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.05em;">
                            <?= h($class->class_code) ?>
                        </span>
                        <span class="admin-badge <?= $statusBadge ?>"><?= ucfirst(h($class->class_status ?? '')) ?></span>
                    </div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 6px;">
                        <?= h($class->course?->course_name ?? 'Class') ?>
                    </div>
                    <div class="d-flex flex-wrap gap-3" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                        <span class="d-flex align-items-center gap-1">
                            <i class="bi bi-clock"></i>
                            <?= $class->start_datetime ? h($class->start_datetime->format('g:ia')) : '-' ?>
                            <?php if ($class->end_datetime): ?>
                                &ndash; <?= h($class->end_datetime->format('g:ia')) ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($class->location): ?>
                            <span class="d-flex align-items-center gap-1">
                                <i class="bi bi-geo-alt"></i><?= h($class->location) ?>
                            </span>
                        <?php endif; ?>
                        <span class="d-flex align-items-center gap-1">
                            <i class="bi bi-people"></i><?= count($class->bookings) ?> student<?= count($class->bookings) !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>

                <i class="bi bi-chevron-right" style="color: var(--admin-text-secondary); flex-shrink: 0;"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
