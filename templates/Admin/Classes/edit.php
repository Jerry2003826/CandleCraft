<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \Cake\ORM\ResultSet $courses
 * @var \Cake\ORM\ResultSet $teachers
 * @var array<string, string> $locationOptions
 */
$this->assign('title', 'Edit Class');
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Classes
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit Class: <?= h($class->class_code) ?></h2>
    </div>
    
    <?= $this->Form->create($class) ?>
        <div class="admin-form-group">
            <label for="class-code" class="admin-form-label">Class Code</label>
            <?= $this->Form->text('class_code', [
                'id' => 'class-code', 
                'readonly' => true,
                'class' => 'admin-form-input'
            ]) ?>
            <p style="margin: 8px 0 0; font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                This code is generated automatically. If you change the course and save, the code will be regenerated to match.
            </p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="course-id" class="admin-form-label">Course</label>
                    <?= $this->Form->select('course_id', $courses, [
                        'id' => 'course-id', 
                        'empty' => '-- Select Course --', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="teacher-id" class="admin-form-label">Teacher</label>
                    <?= $this->Form->select('teacher_id', $teachers, [
                        'id' => 'teacher-id', 
                        'empty' => '-- Select Teacher --', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="start-datetime" class="admin-form-label">Start Date & Time</label>
                    <?= $this->Form->text('start_datetime', [
                        'type' => 'datetime-local', 
                        'id' => 'start-datetime', 
                        'required' => true,
                        'value' => $class->start_datetime?->format('Y-m-d\TH:i') ?? '',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="end-datetime" class="admin-form-label">End Date & Time</label>
                    <?= $this->Form->text('end_datetime', [
                        'type' => 'datetime-local', 
                        'id' => 'end-datetime', 
                        'required' => true,
                        'value' => $class->end_datetime?->format('Y-m-d\TH:i') ?? '',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="location" class="admin-form-label">Location</label>
            <?= $this->Form->select('location', $locationOptions, [
                'id' => 'location', 
                'empty' => '-- Select Location --',
                'required' => true, 
                'class' => 'admin-form-select'
            ]) ?>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="capacity" class="admin-form-label">Capacity</label>
                    <?= $this->Form->number('capacity', [
                        'id' => 'capacity', 
                        'min' => 1, 
                        'max' => 200,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="class-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('class_status', [
                        'scheduled' => 'Scheduled', 
                        'ongoing' => 'Ongoing', 
                        'completed' => 'Completed', 
                        'cancelled' => 'Cancelled', 
                        'full' => 'Full'
                    ], [
                        'id' => 'class-status',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Update Class', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
