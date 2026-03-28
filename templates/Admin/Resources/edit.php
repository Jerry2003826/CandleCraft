<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 * @var array $classOptions
 * @var array $resourceTypes
 */
$this->assign('title', 'Edit Resource');
?>

<div class="card">
    <div class="card-header">
        <h3>Edit Resource: <?= h($resource->resource_name) ?></h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back</a>
    </div>
    <div class="card-body">
        <?= $this->Form->create($resource, ['type' => 'file']) ?>
        <fieldset>
            <?= $this->Form->control('class_id', [
                'type' => 'select',
                'options' => $classOptions,
                'label' => 'Class',
                'required' => true,
            ]) ?>
            <?= $this->Form->control('resource_name', [
                'label' => 'Resource Name',
                'required' => true,
            ]) ?>
            <?= $this->Form->control('resource_description', [
                'type' => 'textarea',
                'label' => 'Description',
            ]) ?>
            <?= $this->Form->control('resource_type', [
                'type' => 'select',
                'options' => $resourceTypes,
                'label' => 'Resource Type',
                'required' => true,
            ]) ?>
            <?= $this->Form->control('resource_url', [
                'label' => 'URL / Link (for external links)',
                'placeholder' => 'https://...',
            ]) ?>
            <?= $this->Form->control('file_upload', [
                'type' => 'file',
                'label' => 'Upload New File (leave empty to keep current)',
            ]) ?>
            <?php if ($resource->file_path): ?>
                <p style="color: #666; font-size: 0.9em;">Current file: <?= h($resource->file_path) ?></p>
            <?php endif; ?>
        </fieldset>
        <?= $this->Form->button('Save Changes', ['class' => 'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
