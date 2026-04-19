<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var array $resources
 */
$this->assign('title', 'Learning Center');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="admin-form-title m-0" style="font-size: 18px;">My Learning Resources</h2>
</div>

<?php if ($bookings->isEmpty()): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-book" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="color: var(--admin-text-secondary);">No classes linked yet. Learning resources will appear here once classes are booked.</p>
    </div>
<?php elseif (empty($resources)): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-folder" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="color: var(--admin-text-secondary);">No learning resources have been uploaded for your classes yet.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
            <?php foreach ($bookings as $booking): ?>
                <?php $classResources = $resources[$booking->class_id] ?? null; ?>
                <?php if ($classResources): ?>
                    <div class="col-12">
                        <div class="admin-form-card" style="padding: 24px; max-width: 100%;">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
                                <div style="width: 48px; height: 48px; background-color: var(--admin-search-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-journal-text" style="font-size: 24px; color: var(--admin-brand-icon);"></i>
                                </div>
                                <div>
                                    <h3 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 18px; color: var(--admin-text-primary); margin: 0 0 4px 0;">
                                        <?= h($booking->class_entity?->course?->course_name ?? 'Class Resources') ?>
                                    </h3>
                                    <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                                        <?= h($booking->class_entity?->class_code ?? 'Class') ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($classResources as $resource): ?>
                                    <div style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; transition: border-color 0.2s;">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                                                    <?= h($resource->resource_name) ?>
                                                </strong>
                                                <span class="admin-badge admin-badge-info" style="padding: 2px 8px; font-size: 11px;">
                                                    <?= ucfirst(h($resource->resource_type)) ?>
                                                </span>
                                            </div>
                                            <?php if ($resource->resource_description): ?>
                                                <p style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); margin: 0;">
                                                    <?= h($resource->resource_description) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if ($resource->resource_type === 'link' && $resource->resource_url): ?>
                                                <a href="<?= h($resource->resource_url) ?>" target="_blank" rel="noopener noreferrer" class="admin-btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open
                                                </a>
                                            <?php elseif ($resource->file_path): ?>
                                                <a href="<?= $this->Url->build(['action' => 'download', $resource->resource_id]) ?>" class="admin-btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                                                    <i class="bi bi-download me-1"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= $this->Url->build(['action' => 'view', $resource->resource_id]) ?>" class="admin-action-link view" style="padding: 6px 16px; height: auto; width: auto; font-size: 13px;">
                                                    View
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
