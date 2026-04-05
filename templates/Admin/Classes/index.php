<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ClassEntity> $classes
 * @var string|null $status
 */
$this->assign('title', 'Classes');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div class="btn-group btn-group-sm flex-wrap" role="group">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-primary <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'scheduled']]) ?>" class="btn btn-outline-primary <?= $status === 'scheduled' ? 'active' : '' ?>">Scheduled</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'ongoing']]) ?>" class="btn btn-outline-primary <?= $status === 'ongoing' ? 'active' : '' ?>">Ongoing</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'completed']]) ?>" class="btn btn-outline-primary <?= $status === 'completed' ? 'active' : '' ?>">Completed</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'cancelled']]) ?>" class="btn btn-outline-primary <?= $status === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Class</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Class Code</th><th>Course</th><th>Teacher</th><th>Start</th><th>Location</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                <tr>
                    <td><strong><?= h($class->class_code) ?></strong></td>
                    <td><?= $class->course ? h($class->course->course_name) : '-' ?></td>
                    <td><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></td>
                    <td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= h($class->location) ?></td>
                    <td><?= h($class->capacity) ?></td>
                    <td><?= $this->Badge->status($class->class_status) ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="btn btn-outline-primary">View</a>
                            <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>" class="btn btn-outline-warning">Edit</a>
                            <?= $this->Form->postLink('Delete', ['action' => 'delete', $class->class_id], [
                                'confirm' => __('Are you sure you want to delete class {0}?', $class->class_code),
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
