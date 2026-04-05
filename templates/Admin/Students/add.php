<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Add Student');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Students</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Add Student</h5></div>
    <div class="card-body">
        <?= $this->Form->create($student) ?>
            <div class="mb-3">
                <label for="student-name" class="form-label">Student Name</label>
                <?= $this->Form->text('student_name', ['id' => 'student-name', 'required' => true, 'maxlength' => 100, ]) ?>
            </div>
            <div class="mb-3">
                <label for="date-of-birth" class="form-label">Date of Birth</label>
                <?= $this->Form->date('date_of_birth', ['id' => 'date-of-birth', 'required' => true, ]) ?>
            </div>
            <div class="mb-3">
                <label for="student-status" class="form-label">Status</label>
                <?= $this->Form->select('student_status', ['active' => 'Active', 'inactive' => 'Inactive'], ['id' => 'student-status', 'default' => 'active', ]) ?>
            </div>
            <div class="mb-3">
                <label for="medical-notes" class="form-label">Medical Notes</label>
                <?= $this->Form->textarea('medical_notes', ['id' => 'medical-notes', 'rows' => 4, 'maxlength' => 1000, ]) ?>
            </div>
            <?= $this->Form->button(__('Save Student'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
