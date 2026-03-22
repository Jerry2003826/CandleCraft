<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Teacher Details');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Teachers</a>
    <div>
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
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= h($teacher->teacher_name) ?></h3>
        <span class="badge badge-<?= h($teacher->teacher_status) ?>">
            <?= ucfirst(h($teacher->teacher_status)) ?>
        </span>
    </div>
    <div class="card-body">
        <table class="detail-table">
            <tr>
                <th>Name:</th>
                <td><?= h($teacher->teacher_name) ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?= $teacher->user ? h($teacher->user->email) : '-' ?></td>
            </tr>
            <tr>
                <th>Phone:</th>
                <td><?= h($teacher->phone_number ?: 'N/A') ?></td>
            </tr>
            <tr>
                <th>Specialization:</th>
                <td><?= h($teacher->specialization ?: 'N/A') ?></td>
            </tr>
            <tr>
                <th>Hire Date:</th>
                <td><?= $teacher->hire_date ? $teacher->hire_date->format('j M Y') : 'N/A' ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge badge-<?= h($teacher->teacher_status) ?>">
                        <?= ucfirst(h($teacher->teacher_status)) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($teacher->classes)): ?>
<div class="card">
    <div class="card-header">
        <h3>Assigned Classes</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Class Code</th>
                <th>Course</th>
                <th>Start</th>
                <th>End</th>
                <th>Location</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teacher->classes as $class): ?>
                <tr>
                    <td><?= h($class->class_code) ?></td>
                    <td><?= $class->course ? h($class->course->course_name) : '-' ?></td>
                    <td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= $class->end_datetime ? $class->end_datetime->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= h($class->location) ?></td>
                    <td>
                        <span class="badge badge-<?= h($class->class_status) ?>">
                            <?= ucfirst(h($class->class_status)) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
