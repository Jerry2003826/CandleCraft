<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Teacher> $teachers
 * @var string|null $status
 */
$this->assign('title', 'Teachers');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div class="btn-group btn-group-sm" role="group">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-primary <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'active']]) ?>" class="btn btn-outline-primary <?= $status === 'active' ? 'active' : '' ?>">Active</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'inactive']]) ?>" class="btn btn-outline-primary <?= $status === 'inactive' ? 'active' : '' ?>">Inactive</a>
    </div>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Teacher</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Specialization</th><th>Phone</th><th>Status</th><th>Hire Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td><?= h($teacher->teacher_name) ?></td>
                    <td><?= h($teacher->specialization ?: '-') ?></td>
                    <td><?= h($teacher->phone_number ?: '-') ?></td>
                    <td><?= $this->Badge->status($teacher->teacher_status) ?></td>
                    <td><?= $teacher->hire_date ? $teacher->hire_date->format('j M Y') : '-' ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= $this->Url->build(['action' => 'view', $teacher->teacher_id]) ?>" class="btn btn-outline-primary">View</a>
                            <a href="<?= $this->Url->build(['action' => 'edit', $teacher->teacher_id]) ?>" class="btn btn-outline-warning">Edit</a>
                            <?= $this->Form->postLink('Delete', ['action' => 'delete', $teacher->teacher_id], [
                                'confirm' => __('Are you sure you want to delete {0}?', $teacher->teacher_name),
                                'class' => 'btn btn-outline-danger',
                            ]) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-center">
        <ul class="pagination mb-0">
            <?= $this->Paginator->prev('< Previous') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next >') ?>
        </ul>
    </div>
</div>
