<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Add Teacher');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Teachers</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Add Teacher</h5></div>
    <div class="card-body">
        <?= $this->Form->create($teacher) ?>
            <h6 class="text-muted text-uppercase small mb-3">Account Information</h6>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <?= $this->Form->text('username', ['id' => 'username', 'required' => true, 'maxlength' => 50, 'pattern' => '[A-Za-z0-9_.-]+', ]) ?>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <?= $this->Form->email('email', ['id' => 'email', 'required' => true, ]) ?>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <?= $this->Form->password('password', ['id' => 'password', 'required' => true, 'minlength' => 8, ]) ?>
            </div>

            <hr class="my-4">
            <h6 class="text-muted text-uppercase small mb-3">Teacher Profile</h6>
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
                <?= $this->Form->select('teacher_status', ['active' => 'Active', 'inactive' => 'Inactive'], ['id' => 'teacher-status', 'default' => 'active', ]) ?>
            </div>
            <div class="mb-3">
                <label for="hire-date" class="form-label">Hire Date</label>
                <?= $this->Form->date('hire_date', ['id' => 'hire-date', ]) ?>
            </div>
            <?= $this->Form->button(__('Save Teacher'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
