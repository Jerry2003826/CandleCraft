<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Edit Teacher');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Teachers</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Edit: <?= h($teacher->teacher_name) ?></h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create($teacher) ?>
            <div class="form-group">
                <label for="teacher-name">Teacher Name</label>
                <?= $this->Form->text('teacher_name', [
                    'id' => 'teacher-name',
                    'required' => true,
                    'maxlength' => 100,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="phone-number">Phone Number</label>
                <?= $this->Form->text('phone_number', [
                    'id' => 'phone-number',
                    'maxlength' => 30,
                    'pattern' => '\+?[0-9\s()-]{8,30}',
                ]) ?>
            </div>
            <div class="form-group">
                <label for="specialization">Specialization</label>
                <?= $this->Form->select('specialization', [
                    'pottery' => 'Pottery',
                    'knitting' => 'Knitting',
                    'both' => 'Pottery & Knitting',
                ], ['id' => 'specialization', 'empty' => '-- Select --']) ?>
            </div>
            <div class="form-group">
                <label for="teacher-status">Status</label>
                <?= $this->Form->select('teacher_status', [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ], ['id' => 'teacher-status']) ?>
            </div>
            <div class="form-group">
                <label for="hire-date">Hire Date</label>
                <?= $this->Form->date('hire_date', [
                    'id' => 'hire-date',
                ]) ?>
            </div>
            <?= $this->Form->button(__('Update Teacher'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn" style="margin-left: 8px;">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
