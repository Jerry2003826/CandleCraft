<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Student Details');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Students</a>
    <div>
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
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= h($student->student_name) ?></h3>
        <span class="badge badge-<?= h($student->student_status) ?>">
            <?= ucfirst(h($student->student_status)) ?>
        </span>
    </div>
    <div class="card-body">
        <table class="detail-table">
            <tr>
                <th>Name:</th>
                <td><?= h($student->student_name) ?></td>
            </tr>
            <tr>
                <th>Date of Birth:</th>
                <td><?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge badge-<?= h($student->student_status) ?>">
                        <?= ucfirst(h($student->student_status)) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Medical Notes:</th>
                <td><?= $student->medical_notes ? nl2br(h($student->medical_notes)) : 'N/A' ?></td>
            </tr>
            <tr>
                <th>Created:</th>
                <td><?= $student->created_at ? $student->created_at->format('j M Y, g:ia') : '-' ?></td>
            </tr>
            <tr>
                <th>Updated:</th>
                <td><?= $student->updated_at ? $student->updated_at->format('j M Y, g:ia') : '-' ?></td>
            </tr>
        </table>
    </div>
</div>
