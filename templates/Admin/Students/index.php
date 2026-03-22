<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Student> $students
 * @var string|null $status
 */
$this->assign('title', 'Students');
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
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success">+ Add Student</a>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Date of Birth</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= h($student->student_name) ?></td>
                    <td><?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?></td>
                    <td>
                        <span class="badge badge-<?= h($student->student_status) ?>">
                            <?= ucfirst(h($student->student_status)) ?>
                        </span>
                    </td>
                    <td><?= $student->created_at ? $student->created_at->format('j M Y') : '-' ?></td>
                    <td class="actions">
                        <a href="<?= $this->Url->build(['action' => 'view', $student->student_id]) ?>"
                           class="btn btn-sm btn-primary">View</a>
                        <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>"
                           class="btn btn-sm btn-warning">Edit</a>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'delete', $student->student_id],
                            [
                                'confirm' => __('Are you sure you want to delete {0}?', $student->student_name),
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
