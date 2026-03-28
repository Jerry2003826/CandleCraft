<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $resources
 */
$this->assign('title', 'My Resources');
?>

<div class="card">
    <div class="card-header">
        <h3>My Learning Resources</h3>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-sm btn-primary">+ Add Resource</a>
    </div>
    <?php if (empty($resources) || (is_object($resources) && $resources->isEmpty())): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4DA;</div>
            <p>No resources uploaded yet. Add materials for your classes.</p>
        </div>
    <?php else: ?>
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
                <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td>
                            <strong><?= h($resource->resource_name) ?></strong>
                            <?php if ($resource->resource_description): ?>
                                <br><small style="color: #7f8c8d;"><?= h(\Cake\Utility\Text::truncate($resource->resource_description, 60)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= h($resource->class_entity ? $resource->class_entity->class_code : '-') ?>
                            <?php if ($resource->class_entity && $resource->class_entity->course): ?>
                                <br><small style="color: #7f8c8d;"><?= h($resource->class_entity->course->course_name) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-scheduled"><?= h(ucfirst($resource->resource_type)) ?></span></td>
                        <td><span class="badge badge-<?= $resource->resource_status === 'active' ? 'confirmed' : 'archived' ?>"><?= h(ucfirst($resource->resource_status)) ?></span></td>
                        <td><?= $resource->uploaded_at ? $resource->uploaded_at->format('j M Y') : '-' ?></td>
                        <td>
                            <div class="actions">
                                <a href="<?= $this->Url->build(['action' => 'edit', $resource->resource_id]) ?>" class="btn btn-sm">Edit</a>
                                <?= $this->Form->postLink('Delete', ['action' => 'delete', $resource->resource_id], [
                                    'class' => 'btn btn-sm btn-danger',
                                    'confirm' => 'Are you sure you want to delete this resource?',
                                ]) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
