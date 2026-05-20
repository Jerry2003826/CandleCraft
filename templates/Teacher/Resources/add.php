<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LearningResource $resource
 * @var array $classOptions
 * @var array $resourceTypes
 */
$this->assign('title', 'Add Resource');
?>

<button onclick="history.back()" class="admin-btn-secondary mb-4" style="display: inline-flex; align-items: center; gap: 8px;">
    <i class="bi bi-arrow-left"></i> Back
</button>

<div class="admin-form-card">
    <?php if (empty($classOptions)): ?>
        <div role="alert" style="padding: 16px 20px; background: rgba(180, 30, 20, 0.45); border: 2px solid #ff5c4d; border-radius: 12px; color: #fff0ee; font-family: 'Inter', sans-serif; font-size: 15px;">
            You have no classes assigned. Resources can only be added to your classes.
        </div>
    <?php else: ?>
        <?= $this->Form->create($resource, ['type' => 'file', 'novalidate' => true, 'id' => 'resource-add-form']) ?>
            <?php
                $serverErrors = [];
                foreach ($resource->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $msg) {
                        $serverErrors[] = ucwords(str_replace('_', ' ', $field)) . ': ' . $msg;
                    }
                }
            ?>
            <?php if (!empty($serverErrors)): ?>
                <div role="alert" style="margin-bottom: 24px; padding: 16px 20px; background: rgba(180, 30, 20, 0.45); border: 2px solid #ff5c4d; border-radius: 12px; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 15px;">
                    <p style="margin: 0 0 8px; font-weight: 600;">Please fix the following errors before submitting:</p>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($serverErrors as $err): ?>
                            <li><?= h($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div id="resource-error-summary" role="alert" tabindex="-1" hidden style="margin-bottom: 24px; padding: 16px 20px; background: rgba(180, 30, 20, 0.45); border: 2px solid #ff5c4d; border-radius: 12px; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 15px;">
                <p style="margin: 0 0 8px; font-weight: 600;">Please fix the following errors before submitting:</p>
                <ul id="resource-error-list" style="margin: 0; padding-left: 20px;"></ul>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="admin-form-group mb-0">
                        <label for="resource-name" class="admin-form-label">Resource Name*</label>
                        <?= $this->Form->text('resource_name', [
                            'id' => 'resource-name', 
                            'required' => true,
                            'class' => 'admin-form-input'
                        ]) ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-form-group mb-0">
                        <label for="class-id" class="admin-form-label">Class*</label>
                        <?= $this->Form->select('class_id', $classOptions, [
                            'id' => 'class-id',
                            'required' => true,
                            'class' => 'admin-form-select',
                            'empty' => 'Please select a class',
                        ]) ?>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group mt-4">
                <label for="resource-type" class="admin-form-label">Resource Type*</label>
                <?= $this->Form->select('resource_type', $resourceTypes, [
                    'id' => 'resource-type',
                    'required' => true,
                    'class' => 'admin-form-select',
                    'style' => 'max-width: 300px;',
                    'empty' => 'Please select a type',
                ]) ?>
            </div>

            <div class="admin-form-group mt-4">
                <label for="resource-description" class="admin-form-label">Description*</label>
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
                        <label for="file-upload" class="admin-form-label">Upload File (PDF, document, video, etc.)</label>
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
        <style>
            .admin-form-input.is-invalid,
            .admin-form-select.is-invalid,
            .admin-form-textarea.is-invalid {
                border-color: #ff5c4d !important;
                background: #fff3f1 !important;
                box-shadow: 0 0 0 3px rgba(255, 92, 77, 0.22) !important;
            }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('resource-add-form');
            if (!form) return;
            var summary = document.getElementById('resource-error-summary');
            var list = document.getElementById('resource-error-list');

            var validatedFields = [
                { id: 'resource-name',        label: 'Resource Name' },
                { id: 'class-id',             label: 'Class' },
                { id: 'resource-type',        label: 'Resource Type' },
                { id: 'resource-description', label: 'Description' },
            ];

            function getErrors() {
                var errors = [];

                validatedFields.forEach(function (f) {
                    var el = document.getElementById(f.id);
                    if (!el) return;
                    if (el.value.trim() === '') {
                        el.classList.add('is-invalid');
                        errors.push({ id: f.id, label: f.label, msg: 'This field is required.' });
                    } else {
                        el.classList.remove('is-invalid');
                    }
                });

                var urlEl  = document.getElementById('resource-url');
                var fileEl = document.getElementById('file-upload');
                var hasUrl  = urlEl  && urlEl.value.trim() !== '';
                var hasFile = fileEl && fileEl.files && fileEl.files.length > 0;
                if (hasUrl && hasFile) {
                    if (urlEl)  urlEl.classList.add('is-invalid');
                    if (fileEl) fileEl.classList.add('is-invalid');
                    errors.push({ id: 'resource-url', label: 'Resource Content', msg: 'Provide a URL or a file, not both.' });
                } else if (!hasUrl && !hasFile) {
                    if (urlEl)  urlEl.classList.add('is-invalid');
                    if (fileEl) fileEl.classList.add('is-invalid');
                    errors.push({ id: 'resource-url', label: 'Resource Content', msg: 'Provide a URL or upload a file.' });
                } else {
                    if (urlEl)  urlEl.classList.remove('is-invalid');
                    if (fileEl) fileEl.classList.remove('is-invalid');
                }

                return errors;
            }

            form.addEventListener('submit', function (e) {
                var errors = getErrors();
                if (errors.length === 0) {
                    summary.hidden = true;
                    return;
                }
                e.preventDefault();
                list.innerHTML = '';
                errors.forEach(function (err) {
                    var li = document.createElement('li');
                    var strong = document.createElement('strong');
                    strong.textContent = err.label;
                    li.appendChild(strong);
                    li.appendChild(document.createTextNode(': ' + err.msg));
                    list.appendChild(li);
                });
                summary.hidden = false;
                summary.focus();
                summary.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            validatedFields.concat([
                { id: 'resource-url' }, { id: 'file-upload' }
            ]).forEach(function (f) {
                var el = document.getElementById(f.id);
                if (!el) return;
                el.addEventListener('input',  function () { el.classList.remove('is-invalid'); });
                el.addEventListener('change', function () { el.classList.remove('is-invalid'); });
            });
        });
        </script>
    <?php endif; ?>
</div>
