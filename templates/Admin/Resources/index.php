<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $resources
 * @var iterable $classes
 * @var string|null $filter
 * @var string $status
 */
$this->assign('title', 'Learning Resources');
$resourceList = is_object($resources) && method_exists($resources, 'items')
    ? $resources->items()
    : (is_array($resources) ? $resources : iterator_to_array($resources));
?>

<div class="admin-page-header admin-list-toolbar">
    <?php if ($status === 'active'): ?>
        <form method="get" class="admin-list-toolbar__leading">
            <input type="hidden" name="status" value="active">
            <div class="admin-search admin-list-toolbar__search">
                <i class="bi bi-filter" aria-hidden="true"></i>
                <label for="resource-class-filter" class="visually-hidden">Filter resources by class</label>
                <select id="resource-class-filter" name="class_id" onchange="this.form.submit()">
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
        <div class="admin-list-toolbar__actions">
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
                <i class="bi bi-plus-lg"></i> Add Resource
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="admin-tabs mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= $status === 'active' ? 'active' : '' ?>">Active</a>
    <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'archived']]) ?>" class="admin-tab <?= $status === 'archived' ? 'active' : '' ?>">Archived</a>
</div>

<?php if ($resourceList === []): ?>
    <div class="admin-table-card text-center py-5">
        <i class="bi bi-folder-x" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <?php if ($status === 'archived'): ?>
            <p class="mt-3" style="color: var(--admin-text-secondary);">No archived resources.</p>
        <?php else: ?>
            <p class="mt-3" style="color: var(--admin-text-secondary);">No resources found.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary mt-3 mx-auto">
                Add Resource
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Type</th>
                        <th>Uploaded</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resourceList as $resource): ?>
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
                            <p class="admin-table-secondary-text"><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></p>
                        </td>
                        <td>
                            <div class="admin-action-links justify-content-end">
                                <?php if ($status === 'active'): ?>
                                    <?php if ($resource->file_path): ?>
                                        <a href="<?= $this->Url->build(['action' => 'download', $resource->resource_id]) ?>" class="admin-action-link view" title="Download" aria-label="Download <?= h($resource->resource_name) ?>">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php elseif ($resource->resource_url): ?>
                                        <a href="<?= h($resource->resource_url) ?>" target="_blank" rel="noopener noreferrer" class="admin-action-link view" title="Open Link" aria-label="Open link for <?= h($resource->resource_name) ?>">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= $this->Url->build(['action' => 'edit', $resource->resource_id]) ?>" class="admin-action-link edit" title="Edit" aria-label="Edit <?= h($resource->resource_name) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= $this->Form->create(null, ['url' => ['action' => 'archive', $resource->resource_id], 'class' => 'd-inline m-0']) ?>
                                        <?= $this->Form->button('<i class="bi bi-archive"></i>', [
                                            'class' => 'admin-action-link edit',
                                            'type' => 'submit',
                                            'title' => 'Archive',
                                            'aria-label' => 'Archive ' . h($resource->resource_name),
                                            'onclick' => "return confirm('Archive this resource? Students will no longer see it.');",
                                            'escapeTitle' => false,
                                        ]) ?>
                                    <?= $this->Form->end() ?>
                                    <?= $this->Form->create(null, ['url' => ['action' => 'delete', $resource->resource_id], 'class' => 'd-inline m-0']) ?>
                                        <?= $this->Form->button('<i class="bi bi-trash"></i>', [
                                            'class' => 'admin-action-link delete',
                                            'type' => 'submit',
                                            'title' => 'Delete',
                                            'aria-label' => 'Delete ' . h($resource->resource_name),
                                            'onclick' => "return confirm('Are you sure you want to delete this resource?');",
                                            'escapeTitle' => false,
                                        ]) ?>
                                    <?= $this->Form->end() ?>
                                <?php else: ?>
                                    <?= $this->Form->create(null, ['url' => ['action' => 'restore', $resource->resource_id], 'class' => 'd-inline m-0']) ?>
                                        <?= $this->Form->button('<i class="bi bi-arrow-counterclockwise"></i>', [
                                            'class' => 'admin-action-link view',
                                            'type' => 'submit',
                                            'title' => 'Restore',
                                            'aria-label' => 'Restore ' . h($resource->resource_name),
                                            'escapeTitle' => false,
                                        ]) ?>
                                    <?= $this->Form->end() ?>
                                    <?= $this->Form->create(null, ['url' => ['action' => 'delete', $resource->resource_id], 'class' => 'd-inline m-0']) ?>
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
