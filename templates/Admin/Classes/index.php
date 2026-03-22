<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ClassEntity> $classes
 * @var string|null $status
 */
$this->assign('title', 'Classes');
?>

<div class="toolbar">
    <div class="filters">
        <span>Status:</span>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>"
           class="btn btn-sm <?= !$status ? 'btn-primary' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'scheduled']]) ?>"
           class="btn btn-sm <?= $status === 'scheduled' ? 'btn-primary' : '' ?>">Scheduled</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'ongoing']]) ?>"
           class="btn btn-sm <?= $status === 'ongoing' ? 'btn-primary' : '' ?>">Ongoing</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'completed']]) ?>"
           class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : '' ?>">Completed</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'cancelled']]) ?>"
           class="btn btn-sm <?= $status === 'cancelled' ? 'btn-primary' : '' ?>">Cancelled</a>
    </div>
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success">+ Add Class</a>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Class Code</th>
                <th>Course</th>
                <th>Teacher</th>
                <th>Start</th>
                <th>Location</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $class): ?>
                <tr>
                    <td><strong><?= h($class->class_code) ?></strong></td>
                    <td><?= $class->course ? h($class->course->course_name) : '-' ?></td>
                    <td><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></td>
                    <td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= h($class->location) ?></td>
                    <td><?= h($class->capacity) ?></td>
                    <td>
                        <span class="badge badge-<?= h($class->class_status) ?>">
                            <?= ucfirst(h($class->class_status)) ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>"
                           class="btn btn-sm btn-primary">View</a>
                        <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>"
                           class="btn btn-sm btn-warning">Edit</a>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'delete', $class->class_id],
                            [
                                'confirm' => __('Are you sure you want to delete class {0}?', $class->class_code),
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
