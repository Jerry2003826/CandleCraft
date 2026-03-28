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
            <div class="form-group">
                <?= $this->Form->control('class_id', [
                    'type' => 'select',
                    'options' => $classOptions,
                    'label' => 'Class',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <?= $this->Form->control('resource_name', [
                    'label' => 'Resource Name',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <?= $this->Form->control('resource_description', [
                    'type' => 'textarea',
                    'label' => 'Description',
                ]) ?>
            </div>
            <div class="form-group">
                <?= $this->Form->control('resource_type', [
                    'type' => 'select',
                    'options' => $resourceTypes,
                    'label' => 'Resource Type',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <?= $this->Form->control('resource_url', [
                    'label' => 'URL / Link',
                    'placeholder' => 'https://...',
                ]) ?>
            </div>
            <div class="form-group">
                <?= $this->Form->control('resource_status', [
                    'type' => 'select',
                    'options' => ['active' => 'Active', 'archived' => 'Archived'],
                    'label' => 'Status',
                ]) ?>
            </div>
            <?php if ($resource->file_path): ?>
                <div class="form-group">
                    <label>Current File</label>
                    <p><a href="/<?= h($resource->file_path) ?>" target="_blank"><?= h(basename($resource->file_path)) ?></a></p>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <?= $this->Form->control('file_upload', [
                    'type' => 'file',
                    'label' => 'Replace File (optional)',
                ]) ?>
            </div>
        </fieldset>
        <?= $this->Form->button('Save Changes', ['class' => 'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
