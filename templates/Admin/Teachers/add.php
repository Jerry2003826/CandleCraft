<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Add Teacher');
?>

<a href="#" onclick="history.back(); return false;" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Add Teacher</h2>
    </div>
    
    <?= $this->Form->create($teacher) ?>
        <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Account Information</h3>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="username" class="admin-form-label">Username</label>
                    <?= $this->Form->text('username', [
                        'id' => 'username', 
                        'required' => true, 
                        'maxlength' => 50, 
                        'pattern' => '[A-Za-z0-9_.-]+',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="email" class="admin-form-label">Email</label>
                    <?= $this->Form->email('email', [
                        'id' => 'email', 
                        'required' => true,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="password" class="admin-form-label">Password</label>
            <?= $this->Form->password('password', [
                'id' => 'password', 
                'required' => true, 
                'minlength' => 8,
                'class' => 'admin-form-input'
            ]) ?>
        </div>

        <hr style="border-color: var(--admin-card-border); margin: 32px 0;">
        
        <h3 class="admin-form-title" style="font-size: 16px; margin-bottom: 24px; color: var(--admin-brand-icon);">Teacher Profile</h3>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="teacher-name" class="admin-form-label">Teacher Name</label>
                    <?= $this->Form->text('teacher_name', [
                        'id' => 'teacher-name', 
                        'required' => true, 
                        'maxlength' => 100,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="phone-number" class="admin-form-label">Phone Number</label>
                    <?= $this->Form->text('phone_number', [
                        'id' => 'phone-number', 
                        'maxlength' => 30, 
                        'pattern' => '\+?[0-9\s()-]{8,30}',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-4">
                <div class="admin-form-group mb-0">
                    <label for="specialization" class="admin-form-label">Specialization</label>
                    <?= $this->Form->select('specialization', [
                        'pottery' => 'Pottery', 
                        'knitting' => 'Knitting', 
                        'both' => 'Pottery & Knitting'
                    ], [
                        'id' => 'specialization', 
                        'empty' => '-- Select --',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-form-group mb-0">
                    <label for="teacher-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('teacher_status', [
                        'active' => 'Active', 
                        'inactive' => 'Inactive'
                    ], [
                        'id' => 'teacher-status', 
                        'default' => 'active',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-form-group mb-0">
                    <label for="hire-date" class="admin-form-label">Hire Date</label>
                    <?= $this->Form->date('hire_date', [
                        'id' => 'hire-date',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Save Teacher', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
