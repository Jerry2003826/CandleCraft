<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Course $course
 */
$this->assign('title', 'Edit Course');
?>

<div class="mb-4">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link" onclick="history.back(); return false;">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit Course: <?= h($course->course_name) ?></h2>
    </div>
    
    <?= $this->Form->create($course) ?>
        <div class="admin-form-group">
            <label for="course-name" class="admin-form-label">Course Name</label>
            <?= $this->Form->text('course_name', [
                'id' => 'course-name', 
                'required' => true, 
                'placeholder' => 'e.g. Pottery for Beginners', 
                'maxlength' => 100,
                'class' => 'admin-form-input'
            ]) ?>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="course-type" class="admin-form-label">Course Type</label>
                    <?= $this->Form->text('course_type', [
                        'id' => 'course-type', 
                        'required' => true, 
                        'placeholder' => 'e.g. pottery, knitting, candle', 
                        'maxlength' => 50,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="course-level" class="admin-form-label">Level</label>
                    <?= $this->Form->select('course_level', [
                        'beginner' => 'Beginner', 
                        'intermediate' => 'Intermediate', 
                        'advanced' => 'Advanced', 
                        'all_levels' => 'All Levels'
                    ], [
                        'id' => 'course-level', 
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="course-price" class="admin-form-label">Price ($)</label>
                    <?= $this->Form->text('course_price', [
                        'id' => 'course-price', 
                        'type' => 'number', 
                        'step' => '0.01', 
                        'min' => '0', 
                        'required' => true, 
                        'placeholder' => '0.00',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="is-active" class="admin-form-label">Status</label>
                    <?= $this->Form->select('is_active', [
                        '1' => 'Active', 
                        '0' => 'Inactive'
                    ], [
                        'id' => 'is-active', 
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="course-description" class="admin-form-label">Description</label>
            <?= $this->Form->textarea('course_description', [
                'id' => 'course-description', 
                'rows' => 4, 
                'maxlength' => 2000, 
                'placeholder' => 'A brief description of this course...',
                'class' => 'admin-form-textarea'
            ]) ?>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Save Changes', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
