<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Student Details');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Students</a>
    <div class="btn-group btn-group-sm">
        <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>" class="btn btn-outline-warning">Edit</a>
        <?= $this->Form->postLink('Delete', ['action' => 'delete', $student->student_id], [
            'confirm' => __('Are you sure you want to delete {0}?', $student->student_name),
            'class' => 'btn btn-outline-danger',
        ]) ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($student->student_name) ?></h5>
        <?= $this->Badge->status($student->student_status) ?>
    </div>
    <div class="card-body">
        <table class="detail-table table table-borderless">
            <tr><th class="text-end text-muted" style="width:180px">Name:</th><td><?= h($student->student_name) ?></td></tr>
            <tr><th class="text-end text-muted">Date of Birth:</th><td><?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Status:</th><td><?= $this->Badge->status($student->student_status) ?></td></tr>
            <tr><th class="text-end text-muted">Medical Notes:</th><td><?= $student->medical_notes ? nl2br(h($student->medical_notes)) : 'N/A' ?></td></tr>
            <tr><th class="text-end text-muted">Created:</th><td><?= $student->created_at ? $student->created_at->format('j M Y, g:ia') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Updated:</th><td><?= $student->updated_at ? $student->updated_at->format('j M Y, g:ia') : '-' ?></td></tr>
        </table>
    </div>
</div>
