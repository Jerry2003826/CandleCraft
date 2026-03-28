<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\LearningResource> $resources
 * @var iterable $classes
 * @var string|null $filter
 */
$this->assign('title', 'Learning Resources');
?>

<div class="card">
    <div class="card-header">
        <h3>Learning Resources</h3>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-sm btn-primary">Add Resource</a>
    </div>

    <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <label for="filter-class" style="font-weight: 500;">Filter by Class:</label>
            <select name="class_id" id="filter-class" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;">
                <option value="">All Classes</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= h($class->class_id) ?>" <?= $filter == $class->class_id ? 'selected' : '' ?>>
                        <?= h($class->class_code) ?> - <?= h($class->course ? $class->course->course_name : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($filter): ?>
                <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Class</th>
                <th>Type</th>
                <th>Status</th>
                <th>Uploaded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resources->isEmpty()): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">No resources found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td><?= h($resource->resource_name) ?></td>
                        <td>
                            <?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?>
                            <?php if ($resource->class_entity && $resource->class_entity->course): ?>
                                <br><small style="color: #666;"><?= h($resource->class_entity->course->course_name) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= h($resource->resource_type === 'video' ? 'confirmed' : 'pending') ?>"><?= ucfirst(h($resource->resource_type)) ?></span></td>
                        <td><span class="badge badge-<?= $resource->resource_status === 'active' ? 'confirmed' : 'cancelled' ?>"><?= ucfirst(h($resource->resource_status)) ?></span></td>
                        <td><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></td>
                        <td>
                            <a href="<?= $this->Url->build(['action' => 'edit', $resource->resource_id]) ?>" class="btn btn-sm btn-primary">Edit</a>
                            <?= $this->Form->postLink('Delete', ['action' => 'delete', $resource->resource_id], [
                                'class' => 'btn btn-sm',
                                'confirm' => 'Are you sure you want to delete this resource?',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
