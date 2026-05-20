<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 * @var \App\Model\Entity\ClassEntity[] $upcoming
 * @var \App\Model\Entity\ClassEntity[] $past
 */
$this->assign('title', 'My Schedule');

$statusBadge = static function (string $status): string {
    return match ($status) {
        'scheduled'  => 'admin-badge-info',
        'completed'  => 'admin-badge-success',
        'cancelled'  => 'admin-badge-danger',
        default      => 'admin-badge-neutral',
    };
};
?>

<div class="d-flex justify-content-end mb-4">
    <a href="<?= $this->Url->build(['controller' => 'Availability', 'action' => 'index']) ?>" class="admin-btn-primary" style="display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
        <i class="bi bi-calendar-week"></i> Update Availability
    </a>
</div>

<?php if (empty($upcoming) && empty($past)): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-calendar-x" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-secondary);">
            No classes have been assigned to you yet.
        </p>
    </div>
<?php else: ?>

    <!-- Upcoming classes -->
    <h3 class="admin-form-title mb-4">Upcoming Classes</h3>
    <?php if (empty($upcoming)): ?>
        <div class="admin-form-card text-center py-4 mb-5" style="max-width: 100%;">
            <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">
                No upcoming classes scheduled.
            </p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3 mb-5">
            <?php foreach ($upcoming as $class): ?>
                <?php $badge = $statusBadge($class->class_status ?? ''); ?>
                <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="admin-form-card text-decoration-none" style="padding: 20px 24px; max-width: 100%; display: block; cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-4 flex-wrap">
                            <div style="min-width: 56px; text-align: center; background-color: rgba(59,130,246,0.08); border-radius: 10px; padding: 10px 14px; flex-shrink: 0;">
                                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: #3B82F6; line-height: 1;">
                                    <?= $class->start_datetime ? h($class->start_datetime->format('j')) : '-' ?>
                                </div>
                                <div style="font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 600; color: #3B82F6; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <?= $class->start_datetime ? h($class->start_datetime->format('M')) : '' ?>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.05em;">
                                        <?= h($class->class_code) ?>
                                    </span>
                                    <span class="admin-badge <?= $badge ?>"><?= ucfirst(h($class->class_status ?? '')) ?></span>
                                </div>
                                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 17px; color: var(--admin-text-primary); margin-bottom: 6px;">
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
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Past classes -->
    <?php if (!empty($past)): ?>
        <h3 class="admin-form-title mb-4" style="color: var(--admin-text-secondary);">Past Classes</h3>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($past as $class): ?>
                <?php
                $displayStatus = $class->class_status ?? '';
                $badge = $statusBadge($displayStatus);
                ?>
                <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="admin-form-card text-decoration-none" style="padding: 20px 24px; max-width: 100%; opacity: 0.75; display: block; cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-4 flex-wrap">
                            <div style="min-width: 56px; text-align: center; background-color: var(--admin-search-bg); border-radius: 10px; padding: 10px 14px; flex-shrink: 0;">
                                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: var(--admin-text-secondary); line-height: 1;">
                                    <?= $class->start_datetime ? h($class->start_datetime->format('j')) : '-' ?>
                                </div>
                                <div style="font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 600; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
                                    <?= $class->start_datetime ? h($class->start_datetime->format('M')) : '' ?>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.05em;">
                                        <?= h($class->class_code) ?>
                                    </span>
                                    <span class="admin-badge <?= $badge ?>"><?= ucfirst(h($displayStatus)) ?></span>
                                    <?php if ($displayStatus === 'scheduled'): ?>
                                        <span class="admin-table-secondary-text">Past date</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 17px; color: var(--admin-text-primary); margin-bottom: 6px;">
                                    <?= h($class->course?->course_name ?? 'Class') ?>
                                </div>
                                <div class="d-flex flex-wrap gap-3" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="bi bi-clock"></i>
                                        <?= $class->start_datetime ? h($class->start_datetime->format('D j M Y, g:ia')) : '-' ?>
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
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
