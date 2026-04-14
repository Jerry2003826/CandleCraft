<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $resources
 */
$this->assign('title', 'My Resources');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="admin-form-title m-0" style="font-size: 18px;">My Learning Resources</h2>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Resource
    </a>
</div>

<?php if (empty($resources) || (is_object($resources) && $resources->isEmpty())): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-folder-x" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="color: var(--admin-text-secondary);">No resources uploaded yet. Add materials for your classes.</p>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary mt-3 mx-auto">
            Add Resource
        </a>
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
                        <th>Status</th>
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
                                <p class="admin-table-primary-text mb-1"><?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?></p>
                                <?php if ($resource->class_entity && $resource->class_entity->course): ?>
                                    <p class="admin-table-secondary-text mb-0"><?= h($resource->class_entity->course->course_name) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info"><?= h(ucfirst($resource->resource_type)) ?></span>
                            </td>
                            <td>
                                <?php 
                                    $statusClass = 'admin-badge-neutral';
                                    if ($resource->resource_status === 'active') $statusClass = 'admin-badge-success';
                                    if ($resource->resource_status === 'archived') $statusClass = 'admin-badge-warning';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>"><?= h(ucfirst($resource->resource_status)) ?></span>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text mb-0"><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></p>
                            </td>
                            <td>
                                <div class="admin-action-links justify-content-end">
                                    <a href="<?= $this->Url->build(['action' => 'edit', $resource->resource_id]) ?>" class="admin-action-link edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= $this->Form->postLink('<i class="bi bi-trash"></i>', ['action' => 'delete', $resource->resource_id], [
                                        'class' => 'admin-action-link delete',
                                        'confirm' => 'Are you sure you want to delete this resource?',
                                        'title' => 'Delete',
                                        'escape' => false
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
