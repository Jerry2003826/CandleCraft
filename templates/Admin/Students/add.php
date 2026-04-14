<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Add Student');
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Add Student</h2>
    </div>
    
    <?= $this->Form->create($student) ?>
        <div class="row g-4">
            <div class="col-md-12">
                <div class="admin-form-group mb-0">
                    <label for="student-name" class="admin-form-label">Student Name</label>
                    <?= $this->Form->text('student_name', [
                        'id' => 'student-name', 
                        'required' => true, 
                        'maxlength' => 100,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth</label>
                    <?= $this->Form->date('date_of_birth', [
                        'id' => 'date-of-birth', 
                        'required' => true,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('student_status', [
                        'active' => 'Active', 
                        'inactive' => 'Inactive'
                    ], [
                        'id' => 'student-status', 
                        'default' => 'active',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="medical-notes" class="admin-form-label">Medical Notes</label>
            <?= $this->Form->textarea('medical_notes', [
                'id' => 'medical-notes', 
                'rows' => 4, 
                'maxlength' => 1000,
                'class' => 'admin-form-textarea'
            ]) ?>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Save Student', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
