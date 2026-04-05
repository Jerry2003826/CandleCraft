<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 * @var array $classOptions
 * @var array $resourceTypes
 */
$this->assign('title', 'Add Resource');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Add Learning Resource</h5>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary">&larr; Back</a>
    </div>
    <div class="card-body">
        <?= $this->Form->create($resource, ['type' => 'file']) ?>
        <fieldset>
            <?= $this->Form->control('class_id', ['type' => 'select', 'options' => $classOptions, 'label' => 'Class', 'required' => true]) ?>
            <?= $this->Form->control('resource_name', ['label' => 'Resource Name', 'required' => true]) ?>
            <?= $this->Form->control('resource_description', ['type' => 'textarea', 'label' => 'Description']) ?>
            <?= $this->Form->control('resource_type', ['type' => 'select', 'options' => $resourceTypes, 'label' => 'Resource Type', 'required' => true]) ?>
            <?= $this->Form->control('resource_url', ['label' => 'URL / Link (for external links)', 'placeholder' => 'https://...']) ?>
            <?= $this->Form->control('file_upload', ['type' => 'file', 'label' => 'Upload File (document, video, etc.)']) ?>
        </fieldset>
        <?= $this->Form->button('Add Resource', ['class' => 'btn btn-primary']) ?>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
