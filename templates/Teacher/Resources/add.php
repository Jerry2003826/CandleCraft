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
    <div class="card-header">
        <h3>Add Learning Resource</h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back</a>
    </div>
    <div class="card-body">
        <?php if (empty($classOptions)): ?>
            <div class="empty-state">
                <div class="icon">&#x1F4CB;</div>
                <p>You have no classes assigned. Resources can only be added to your classes.</p>
            </div>
        <?php else: ?>
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
                        'label' => 'URL / Link (for external links)',
                        'placeholder' => 'https://...',
                    ]) ?>
                </div>
                <div class="form-group">
                    <?= $this->Form->control('file_upload', [
                        'type' => 'file',
                        'label' => 'Upload File (PDF, document, video, etc.)',
                    ]) ?>
                </div>
            </fieldset>
            <?= $this->Form->button('Add Resource', ['class' => 'btn btn-primary']) ?>
            <?= $this->Form->end() ?>
        <?php endif; ?>
    </div>
</div>
