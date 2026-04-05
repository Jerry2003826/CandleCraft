<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Student> $students
 * @var string|null $status
 */
$this->assign('title', 'Students');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div class="btn-group btn-group-sm" role="group">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-primary <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'active']]) ?>" class="btn btn-outline-primary <?= $status === 'active' ? 'active' : '' ?>">Active</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'inactive']]) ?>" class="btn btn-outline-primary <?= $status === 'inactive' ? 'active' : '' ?>">Inactive</a>
    </div>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Student</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Date of Birth</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= h($student->student_name) ?></td>
                    <td><?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?></td>
                    <td><?= $this->Badge->status($student->student_status) ?></td>
                    <td><?= $student->created_at ? $student->created_at->format('j M Y') : '-' ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= $this->Url->build(['action' => 'view', $student->student_id]) ?>" class="btn btn-outline-primary">View</a>
                            <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>" class="btn btn-outline-warning">Edit</a>
                            <?= $this->Form->postLink('Delete', ['action' => 'delete', $student->student_id], [
                                'confirm' => __('Are you sure you want to delete {0}?', $student->student_name),
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
