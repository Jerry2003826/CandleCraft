<?php
/**
 * @var \App\View\AppView $this
 * @var array $schedule
 * @var array $daysMap
 */
$this->assign('title', 'My Schedule');
?>

<div class="admin-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <h2 class="admin-form-title m-0">My Weekly Schedule</h2>
    <a href="<?= $this->Url->build(['action' => 'edit']) ?>" class="admin-btn-primary">
        <i class="bi bi-pencil"></i> Edit Schedule
    </a>
</div>

<?php if (empty($schedule)): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-calendar-x text-muted" style="font-size: 48px;"></i>
        <p class="mt-3 text-muted" style="font-family: 'Inter', sans-serif; font-size: 15px;">You haven't set your availability yet.</p>
        <a href="<?= $this->Url->build(['action' => 'edit']) ?>" class="admin-btn-primary mt-3 mx-auto">
            Set Availability
        </a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($daysMap as $dayNum => $dayName): ?>
            <?php if (isset($schedule[$dayName])): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="admin-form-card h-100" style="padding: 24px;">
                        <h3 class="admin-form-title mb-4" style="font-size: 18px; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 16px;">
                            <?= h($dayName) ?>
                        </h3>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($schedule[$dayName] as $slot): ?>
                                <span class="admin-badge admin-badge-info" style="font-size: 13px; padding: 6px 12px;">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= h($slot->start_time?->format('g:ia') ?? '') ?> - <?= h($slot->end_time?->format('g:ia') ?? '') ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
