<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Add Student');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Students</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Add Student</h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create($student) ?>
            <div class="form-group">
                <label for="student-name">Student Name</label>
                <?= $this->Form->text('student_name', [
                    'id' => 'student-name',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="date-of-birth">Date of Birth</label>
                <?= $this->Form->date('date_of_birth', [
                    'id' => 'date-of-birth',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="student-status">Status</label>
                <?= $this->Form->select('student_status', [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ], ['id' => 'student-status', 'default' => 'active']) ?>
            </div>
            <div class="form-group">
                <label for="medical-notes">Medical Notes</label>
                <?= $this->Form->textarea('medical_notes', [
                    'id' => 'medical-notes',
                    'rows' => 4,
                ]) ?>
            </div>
            <?= $this->Form->button(__('Save Student'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn" style="margin-left: 8px;">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
