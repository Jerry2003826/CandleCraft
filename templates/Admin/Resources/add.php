<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 * @var array $classOptions
 * @var array $resourceTypes
 */
$this->assign('title', 'Add Resource');
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Resources
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Add Learning Resource</h2>
    </div>
    
    <?= $this->Form->create($resource, ['type' => 'file']) ?>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="resource-name" class="admin-form-label">Resource Name</label>
                    <?= $this->Form->text('resource_name', [
                        'id' => 'resource-name', 
                        'required' => true,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="class-id" class="admin-form-label">Class</label>
                    <?= $this->Form->select('class_id', $classOptions, [
                        'id' => 'class-id', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="resource-type" class="admin-form-label">Resource Type</label>
                    <?= $this->Form->select('resource_type', $resourceTypes, [
                        'id' => 'resource-type', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="resource-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('resource_status', [
                        'active' => 'Active', 
                        'inactive' => 'Inactive'
                    ], [
                        'id' => 'resource-status', 
                        'default' => 'active',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="admin-form-group mt-4">
            <label for="resource-description" class="admin-form-label">Description</label>
            <?= $this->Form->textarea('resource_description', [
                'id' => 'resource-description', 
                'rows' => 3,
                'class' => 'admin-form-textarea'
            ]) ?>
        </div>

        <hr style="border-color: var(--admin-card-border); margin: 32px 0;">
        <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Resource Content</h3>
        <p style="font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 24px;">Provide either an external URL or upload a file.</p>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="resource-url" class="admin-form-label">URL / Link (for external links)</label>
                    <?= $this->Form->url('resource_url', [
                        'id' => 'resource-url', 
                        'placeholder' => 'https://...',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="file-upload" class="admin-form-label">Upload File (document, video, etc.)</label>
                    <?= $this->Form->file('file_upload', [
                        'id' => 'file-upload',
                        'class' => 'admin-form-input',
                        'style' => 'padding: 7px 16px;'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Add Resource', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
