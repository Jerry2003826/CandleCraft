<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 */
$this->assign('title', h($resource->resource_name));
?>

<div class="z-billing-page">
    <div class="z-billing-header">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link mb-3 d-inline-flex" style="font-size: 13px;">
            <i class="bi bi-arrow-left"></i> Back to Resources
        </a>
        <h1 class="z-billing-title" style="margin-bottom: 0;"><?= h($resource->resource_name) ?></h1>
    </div>

    <div class="z-billing-balance-card" style="padding: 40px;">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div style="font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Type</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 600; color: var(--admin-text-primary);">
                    <?= ucfirst(h($resource->resource_type)) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div style="font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 500; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Class</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 600; color: var(--admin-text-primary);">
                    <?= h($resource->class_entity?->class_code ?? '-') ?>
                </div>
            </div>
        </div>

        <?php if ($resource->resource_description): ?>
            <div style="font-family: 'Inter', sans-serif; font-size: 15px; line-height: 1.6; color: var(--admin-text-secondary); margin-bottom: 32px; max-width: 800px;">
                <?= nl2br(h($resource->resource_description)) ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-3 flex-wrap">
            <?php if ($resource->resource_url): ?>
                <a href="<?= h($resource->resource_url) ?>" target="_blank" class="z-billing-btn-primary">
                    <i class="bi bi-box-arrow-up-right me-2"></i> Open External Link
                </a>
            <?php endif; ?>

            <?php if ($resource->file_path): ?>
                <?php if ($resource->resource_type !== 'video'): ?>
                    <a href="<?= $this->Url->build('/' . $resource->file_path) ?>" target="_blank" class="z-billing-btn-primary" style="background-color: var(--admin-search-bg); color: var(--admin-text-primary); border: 1px solid var(--admin-card-border);">
                        <i class="bi bi-download me-2"></i> Download File
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($resource->file_path && $resource->resource_type === 'video'): ?>
            <div class="mt-5" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--admin-card-border); background-color: #000;">
                <video controls style="width: 100%; max-height: 600px; display: block;">
                    <source src="<?= $this->Url->build('/' . $resource->file_path) ?>">
                    Your browser does not support the video tag.
                </video>
            </div>
        <?php endif; ?>
    </div>
</div>
