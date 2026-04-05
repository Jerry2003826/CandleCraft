<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Teacher Details');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Teachers</a>
    <div class="btn-group btn-group-sm">
        <a href="<?= $this->Url->build(['action' => 'edit', $teacher->teacher_id]) ?>" class="btn btn-outline-warning">Edit</a>
        <?= $this->Form->postLink('Delete', ['action' => 'delete', $teacher->teacher_id], [
            'confirm' => __('Are you sure you want to delete {0}?', $teacher->teacher_name),
            'class' => 'btn btn-outline-danger',
        ]) ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($teacher->teacher_name) ?></h5>
        <?= $this->Badge->status($teacher->teacher_status) ?>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th class="text-end text-muted" style="width:180px">Name:</th><td><?= h($teacher->teacher_name) ?></td></tr>
            <tr><th class="text-end text-muted">Email:</th><td><?= $teacher->user ? h($teacher->user->email) : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Phone:</th><td><?= h($teacher->phone_number ?: 'N/A') ?></td></tr>
            <tr><th class="text-end text-muted">Specialization:</th><td><?= h($teacher->specialization ?: 'N/A') ?></td></tr>
            <tr><th class="text-end text-muted">Hire Date:</th><td><?= $teacher->hire_date ? $teacher->hire_date->format('j M Y') : 'N/A' ?></td></tr>
            <tr><th class="text-end text-muted">Status:</th><td><?= $this->Badge->status($teacher->teacher_status) ?></td></tr>
        </table>
    </div>
</div>

<?php if (!empty($teacher->classes)): ?>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Assigned Classes</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Class Code</th><th>Course</th><th>Start</th><th>End</th><th>Location</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($teacher->classes as $class): ?>
                <tr>
                    <td><?= h($class->class_code) ?></td>
                    <td><?= $class->course ? h($class->course->course_name) : '-' ?></td>
                    <td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= $class->end_datetime ? $class->end_datetime->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= h($class->location) ?></td>
                    <td><?= $this->Badge->status($class->class_status) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
