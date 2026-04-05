<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Edit Teacher');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Teachers</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Edit: <?= h($teacher->teacher_name) ?></h5></div>
    <div class="card-body">
        <?= $this->Form->create($teacher) ?>
            <div class="mb-3">
                <label for="teacher-name" class="form-label">Teacher Name</label>
                <?= $this->Form->text('teacher_name', ['id' => 'teacher-name', 'required' => true, 'maxlength' => 100, ]) ?>
            </div>
            <div class="mb-3">
                <label for="phone-number" class="form-label">Phone Number</label>
                <?= $this->Form->text('phone_number', ['id' => 'phone-number', 'maxlength' => 30, 'pattern' => '\+?[0-9\s()-]{8,30}', ]) ?>
            </div>
            <div class="mb-3">
                <label for="specialization" class="form-label">Specialization</label>
                <?= $this->Form->select('specialization', ['pottery' => 'Pottery', 'knitting' => 'Knitting', 'both' => 'Pottery & Knitting'], ['id' => 'specialization', 'empty' => '-- Select --', ]) ?>
            </div>
            <div class="mb-3">
                <label for="teacher-status" class="form-label">Status</label>
                <?= $this->Form->select('teacher_status', ['active' => 'Active', 'inactive' => 'Inactive'], ['id' => 'teacher-status', ]) ?>
            </div>
            <div class="mb-3">
                <label for="hire-date" class="form-label">Hire Date</label>
                <?= $this->Form->date('hire_date', ['id' => 'hire-date', ]) ?>
            </div>
            <?= $this->Form->button(__('Update Teacher'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
