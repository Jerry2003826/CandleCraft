<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Teacher> $teachers
 * @var string|null $status
 */
$this->assign('title', 'Teachers');
?>

<div class="toolbar">
    <div class="filters">
        <span>Status:</span>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>"
           class="btn btn-sm <?= !$status ? 'btn-primary' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'active']]) ?>"
           class="btn btn-sm <?= $status === 'active' ? 'btn-primary' : '' ?>">Active</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'inactive']]) ?>"
           class="btn btn-sm <?= $status === 'inactive' ? 'btn-primary' : '' ?>">Inactive</a>
    </div>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success">+ Add Teacher</a>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Specialization</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Hire Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td><?= h($teacher->teacher_name) ?></td>
                    <td><?= h($teacher->specialization ?: '-') ?></td>
                    <td><?= h($teacher->phone_number ?: '-') ?></td>
                    <td>
                        <span class="badge badge-<?= h($teacher->teacher_status) ?>">
                            <?= ucfirst(h($teacher->teacher_status)) ?>
                        </span>
                    </td>
                    <td><?= $teacher->hire_date ? $teacher->hire_date->format('j M Y') : '-' ?></td>
                    <td class="actions">
                        <a href="<?= $this->Url->build(['action' => 'view', $teacher->teacher_id]) ?>"
                           class="btn btn-sm btn-primary">View</a>
                        <a href="<?= $this->Url->build(['action' => 'edit', $teacher->teacher_id]) ?>"
                           class="btn btn-sm btn-warning">Edit</a>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'delete', $teacher->teacher_id],
                            [
                                'confirm' => __('Are you sure you want to delete {0}?', $teacher->teacher_name),
                                'class' => 'btn btn-sm btn-danger',
                            ]
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?= $this->Paginator->prev('< Previous') ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next('Next >') ?>
    </div>
</div>
