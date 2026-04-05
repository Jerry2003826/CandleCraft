<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $resources
 * @var iterable $classes
 * @var string|null $filter
 */
$this->assign('title', 'Learning Resources');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Learning Resources</h5>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-sm btn-outline-primary">Add Resource</a>
    </div>
    <div class="px-3 pt-3">
        <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
            <label for="filter-class" class="form-label mb-0 fw-medium">Filter by Class:</label>
            <select name="class_id" id="filter-class" onchange="this.form.submit()" class="form-select form-select-sm" style="width:auto;">
                <option value="">All Classes</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= h($class->class_id) ?>" <?= $filter == $class->class_id ? 'selected' : '' ?>>
                        <?= h($class->class_code) ?> - <?= h($class->course ? $class->course->course_name : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($filter): ?>
                <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Class</th><th>Type</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if ($resources->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No resources found.</td></tr>
                <?php else: ?>
                    <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td><?= h($resource->resource_name) ?></td>
                        <td>
                            <?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?>
                            <?php if ($resource->class_entity && $resource->class_entity->course): ?>
                                <br><small class="text-muted"><?= h($resource->class_entity->course->course_name) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-info"><?= ucfirst(h($resource->resource_type)) ?></span></td>
                        <td>
                            <span class="badge <?= $resource->resource_status === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst(h($resource->resource_status)) ?>
                            </span>
                        </td>
                        <td><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= $this->Url->build(['action' => 'edit', $resource->resource_id]) ?>" class="btn btn-outline-primary">Edit</a>
                                <?= $this->Form->postLink('Delete', ['action' => 'delete', $resource->resource_id], [
                                    'class' => 'btn btn-outline-danger',
                                    'confirm' => 'Are you sure you want to delete this resource?',
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
