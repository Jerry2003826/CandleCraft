<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Add Teacher');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Teachers</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Add Teacher</h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create($teacher) ?>
            <h4 style="margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #dcdde1;">Account Information</h4>
            <div class="form-group">
                <label for="username">Username</label>
                <?= $this->Form->text('username', [
                    'id' => 'username',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <?= $this->Form->email('email', [
                    'id' => 'email',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <?= $this->Form->password('password', [
                    'id' => 'password',
                    'required' => true,
                ]) ?>
            </div>

            <h4 style="margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid #dcdde1;">Teacher Profile</h4>
            <div class="form-group">
                <label for="teacher-name">Teacher Name</label>
                <?= $this->Form->text('teacher_name', [
                    'id' => 'teacher-name',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="phone-number">Phone Number</label>
                <?= $this->Form->text('phone_number', [
                    'id' => 'phone-number',
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
                ], ['id' => 'teacher-status', 'default' => 'active']) ?>
            </div>
            <div class="form-group">
                <label for="hire-date">Hire Date</label>
                <?= $this->Form->date('hire_date', [
                    'id' => 'hire-date',
                ]) ?>
            </div>
            <?= $this->Form->button(__('Save Teacher'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn" style="margin-left: 8px;">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
