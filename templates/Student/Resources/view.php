<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 */
$this->assign('title', h($resource->resource_name));
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Resources</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($resource->resource_name) ?></h5>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="card bg-light border-0 p-3">
                    <div class="stat-label">Type</div>
                    <strong class="mt-1"><?= ucfirst(h($resource->resource_type)) ?></strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card bg-light border-0 p-3">
                    <div class="stat-label">Class</div>
                    <strong class="mt-1"><?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?></strong>
                </div>
            </div>
        </div>

        <?php if ($resource->resource_description): ?>
            <p class="text-muted"><?= h($resource->resource_description) ?></p>
        <?php endif; ?>

        <?php if ($resource->resource_url): ?>
            <a href="<?= h($resource->resource_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Open External Link</a>
        <?php endif; ?>

        <?php if ($resource->file_path): ?>
            <?php if ($resource->resource_type === 'video'): ?>
                <div class="mt-3">
                    <video controls class="w-100" style="max-width: 800px;">
                        <source src="<?= $this->Url->build(['action' => 'download', $resource->resource_id, '?' => ['inline' => '1']]) ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php else: ?>
                <a href="<?= $this->Url->build(['action' => 'download', $resource->resource_id]) ?>" class="btn btn-primary"><i class="bi bi-download me-1"></i> Download File</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
