<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 */
$this->assign('title', h($resource->resource_name));
?>

<div class="card">
    <div class="card-header">
        <h3><?= h($resource->resource_name) ?></h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Resources</a>
    </div>
    <div class="card-body">
        <div class="portal-status-row" style="margin-bottom: 16px;">
            <div>
                <span class="portal-status-row__label">Type</span>
                <strong><?= ucfirst(h($resource->resource_type)) ?></strong>
            </div>
            <div>
                <span class="portal-status-row__label">Class</span>
                <strong><?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?></strong>
            </div>
        </div>

        <?php if ($resource->resource_description): ?>
            <div style="margin: 16px 0; color: #555;">
                <?= h($resource->resource_description) ?>
            </div>
        <?php endif; ?>

        <?php if ($resource->resource_url): ?>
            <div style="margin: 16px 0;">
                <a href="<?= h($resource->resource_url) ?>" target="_blank" class="btn btn-primary">Open External Link</a>
            </div>
        <?php endif; ?>

        <?php if ($resource->file_path): ?>
            <?php if ($resource->resource_type === 'video'): ?>
                <div style="margin: 16px 0;">
                    <video controls style="width: 100%; max-width: 800px;">
                        <source src="<?= $this->Url->build('/' . $resource->file_path) ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php else: ?>
                <div style="margin: 16px 0;">
                    <a href="<?= $this->Url->build('/' . $resource->file_path) ?>" target="_blank" class="btn btn-primary">Download File</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
