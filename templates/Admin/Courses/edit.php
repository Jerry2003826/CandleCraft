<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Course $course
 */
$this->assign('title', 'Edit Course: ' . h($course->course_name));
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Courses</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Edit Course</h5></div>
    <div class="card-body">
        <?= $this->Form->create($course) ?>
            <div class="mb-3">
                <label for="course-name" class="form-label">Course Name</label>
                <?= $this->Form->text('course_name', ['id' => 'course-name', 'required' => true, 'maxlength' => 100, ]) ?>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="course-type" class="form-label">Course Type</label>
                    <?= $this->Form->text('course_type', ['id' => 'course-type', 'required' => true, 'maxlength' => 50, ]) ?>
                </div>
                <div class="col-md-6">
                    <label for="course-level" class="form-label">Level</label>
                    <?= $this->Form->select('course_level', ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'all_levels' => 'All Levels'], ['id' => 'course-level', ]) ?>
                </div>
            </div>
            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label for="course-price" class="form-label">Price ($)</label>
                    <?= $this->Form->text('course_price', ['id' => 'course-price', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'required' => true, ]) ?>
                </div>
                <div class="col-md-6">
                    <label for="is-active" class="form-label">Active</label>
                    <?= $this->Form->select('is_active', ['1' => 'Yes', '0' => 'No'], ['id' => 'is-active', ]) ?>
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label for="course-description" class="form-label">Description</label>
                <?= $this->Form->textarea('course_description', ['id' => 'course-description', 'rows' => 4, 'maxlength' => 2000, ]) ?>
            </div>
            <?= $this->Form->button(__('Save Course'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
