<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $resources
 * @var string $status
 */
$this->assign('title', 'Manage Learning Resources');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center">
    <div class="admin-tabs">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= $status === 'active' ? 'active' : '' ?>">Active</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'archived']]) ?>" class="admin-tab <?= $status === 'archived' ? 'active' : '' ?>">Archived</a>
    </div>
    <?php if ($status === 'active'): ?>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Learning Resource
        </a>
    <?php endif; ?>
</div>

<?php if (empty($resources) || (is_object($resources) && $resources->isEmpty())): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-folder-x" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <?php if ($status === 'archived'): ?>
            <p class="mt-3" style="color: var(--admin-text-secondary);">No archived resources.</p>
        <?php else: ?>
            <p class="mt-3" style="color: var(--admin-text-secondary);">No learning resources uploaded yet. Add materials for your classes.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary mt-3 mx-auto">
                Add Learning Resource
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name & Description</th>
                        <th>Class & Course</th>
                        <th>Type</th>
                        <th>Uploaded</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <tr>
                            <td>
                                <p class="admin-table-primary-text mb-1"><?= h($resource->resource_name) ?></p>
                                <?php if ($resource->resource_description): ?>
                                    <p class="admin-table-secondary-text mb-0"><?= h(\Cake\Utility\Text::truncate($resource->resource_description, 60)) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <p class="admin-table-primary-text mb-1"><?= h($resource->class ? $resource->class->class_code : '-') ?></p>
                                <?php if ($resource->class && $resource->class->course): ?>
                                    <p class="admin-table-secondary-text mb-0"><?= h($resource->class->course->course_name) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info"><?= h(ucfirst($resource->resource_type)) ?></span>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text mb-0"><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></p>
                            </td>
                            <td>
                                <div class="admin-action-links justify-content-end">
                                    <?php if ($status === 'active'): ?>
                                        <a href="<?= $this->Url->build(['action' => 'edit', $resource->resource_id]) ?>" class="admin-action-link edit" title="Edit" aria-label="Edit <?= h($resource->resource_name) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?= $this->Form->create(null, [
                                            'url' => ['action' => 'archive', $resource->resource_id],
                                            'class' => 'd-inline m-0',
                                        ]) ?>
                                            <?= $this->Form->button('<i class="bi bi-archive"></i>', [
                                                'class' => 'admin-action-link edit',
                                                'type' => 'submit',
                                                'title' => 'Archive',
                                                'aria-label' => 'Archive ' . h($resource->resource_name),
                                                'onclick' => "return confirm('Archive this resource? Students will no longer see it.');",
                                                'escapeTitle' => false,
                                            ]) ?>
                                        <?= $this->Form->end() ?>
                                    <?php else: ?>
                                        <?= $this->Form->create(null, [
                                            'url' => ['action' => 'restore', $resource->resource_id],
                                            'class' => 'd-inline m-0',
                                        ]) ?>
                                            <?= $this->Form->button('<i class="bi bi-arrow-counterclockwise"></i>', [
                                                'class' => 'admin-action-link view',
                                                'type' => 'submit',
                                                'title' => 'Restore',
                                                'aria-label' => 'Restore ' . h($resource->resource_name),
                                                'escapeTitle' => false,
                                            ]) ?>
                                        <?= $this->Form->end() ?>
                                        <?= $this->Form->create(null, [
                                            'url' => ['action' => 'delete', $resource->resource_id],
                                            'class' => 'd-inline m-0',
                                        ]) ?>
                                            <?= $this->Form->button('<i class="bi bi-trash"></i>', [
                                                'class' => 'admin-action-link delete',
                                                'type' => 'submit',
                                                'title' => 'Delete Permanently',
                                                'aria-label' => 'Permanently delete ' . h($resource->resource_name),
                                                'onclick' => "return confirm('Permanently delete this resource? This cannot be undone.');",
                                                'escapeTitle' => false,
                                            ]) ?>
                                        <?= $this->Form->end() ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
