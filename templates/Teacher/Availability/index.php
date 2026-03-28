<?php
/**
 * @var \App\View\AppView $this
 * @var array $schedule
 * @var array $daysMap
 */
$this->assign('title', 'My Schedule');
?>

<div class="card">
    <div class="card-header">
        <h3>My Weekly Schedule</h3>
        <a href="<?= $this->Url->build(['action' => 'edit']) ?>" class="btn btn-sm btn-primary">Edit Schedule</a>
    </div>
    <div class="card-body">
        <?php if (empty($schedule)): ?>
            <div class="empty-state">
                <div class="icon">&#x1F4C5;</div>
                <p>You haven't set your availability yet.</p>
                <a href="<?= $this->Url->build(['action' => 'edit']) ?>" class="btn btn-sm btn-primary" style="margin-top: 10px;">Set Availability</a>
            </div>
        <?php else: ?>
            <div class="portal-class-list">
                <?php foreach ($daysMap as $dayNum => $dayName): ?>
                    <?php if (isset($schedule[$dayName])): ?>
                        <section class="portal-class-card">
                            <div class="portal-class-card__header">
                                <div>
                                    <h3><?= h($dayName) ?></h3>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php foreach ($schedule[$dayName] as $slot): ?>
                                    <span class="badge badge-confirmed" style="font-size: 0.95em;">
                                        <?= h($slot->start_time?->format('g:ia') ?? '') ?> -
                                        <?= h($slot->end_time?->format('g:ia') ?? '') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
