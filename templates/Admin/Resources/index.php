<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $resources
 * @var iterable $classes
 * @var string|null $filter
 */
$this->assign('title', 'Learning Resources');
?>

<div class="admin-page-header">
    <form method="get" class="d-flex gap-3 align-items-center flex-wrap">
        <div class="admin-search" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border);">
            <i class="bi bi-filter"></i>
            <select name="class_id" onchange="this.form.submit()" style="border: none; background: transparent; outline: none; color: var(--admin-text-primary); font-family: 'Inter', sans-serif; font-size: 14px;">
                <option value="">All Classes</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= h($class->class_id) ?>" <?= $filter == $class->class_id ? 'selected' : '' ?>>
                        <?= h($class->class_code) ?> - <?= h($class->course ? $class->course->course_name : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($filter): ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab">Clear Filter</a>
        <?php endif; ?>
    </form>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Resource
    </a>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resources->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No resources found.</td></tr>
                <?php else: ?>
                    <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td>
                            <p class="admin-table-primary-text"><?= h($resource->resource_name) ?></p>
                        </td>
                        <td>
                            <p class="admin-table-primary-text"><?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?></p>
                            <?php if ($resource->class_entity && $resource->class_entity->course): ?>
                                <p class="admin-table-secondary-text"><?= h($resource->class_entity->course->course_name) ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-info"><?= ucfirst(h($resource->resource_type)) ?></span>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'admin-badge-neutral';
                                if ($resource->resource_status === 'active') $statusClass = 'admin-badge-success';
                                if ($resource->resource_status === 'archived') $statusClass = 'admin-badge-warning';
                            ?>
                            <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($resource->resource_status)) ?></span>
                        </td>
                        <td>
                            <p class="admin-table-secondary-text"><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></p>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
