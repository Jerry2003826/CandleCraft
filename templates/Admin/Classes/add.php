<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \Cake\ORM\ResultSet $courses
 * @var \Cake\ORM\ResultSet $teachers
 */
$this->assign('title', 'Add Class');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Classes</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Add Class</h5></div>
    <div class="card-body">
        <?= $this->Form->create($class) ?>
            <div class="mb-3">
                <label for="class-code" class="form-label">Class Code</label>
                <?= $this->Form->text('class_code', ['id' => 'class-code', 'required' => true, 'placeholder' => 'e.g. POT-BEG-001', 'maxlength' => 30, 'pattern' => '[A-Za-z0-9-]{3,30}', ]) ?>
            </div>
            <div class="mb-3">
                <label for="course-id" class="form-label">Course</label>
                <?= $this->Form->select('course_id', $courses, ['id' => 'course-id', 'empty' => '-- Select Course --', 'required' => true, ]) ?>
            </div>
            <div class="mb-3">
                <label for="teacher-id" class="form-label">Teacher</label>
                <?= $this->Form->select('teacher_id', $teachers, ['id' => 'teacher-id', 'empty' => '-- Select Teacher --', 'required' => true, ]) ?>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="start-datetime" class="form-label">Start Date & Time</label>
                    <?= $this->Form->text('start_datetime', ['type' => 'datetime-local', 'id' => 'start-datetime', 'required' => true, ]) ?>
                </div>
                <div class="col-md-6">
                    <label for="end-datetime" class="form-label">End Date & Time</label>
                    <?= $this->Form->text('end_datetime', ['type' => 'datetime-local', 'id' => 'end-datetime', 'required' => true, ]) ?>
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label for="location" class="form-label">Location</label>
                <?= $this->Form->text('location', ['id' => 'location', 'required' => true, 'placeholder' => 'e.g. Studio A', 'maxlength' => 150, ]) ?>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="capacity" class="form-label">Capacity</label>
                    <?= $this->Form->number('capacity', ['id' => 'capacity', 'value' => 20, 'min' => 1, 'max' => 200, ]) ?>
                </div>
                <div class="col-md-6">
                    <label for="class-status" class="form-label">Status</label>
                    <?= $this->Form->select('class_status', ['scheduled' => 'Scheduled', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'full' => 'Full'], ['id' => 'class-status', 'default' => 'scheduled', ]) ?>
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label for="notes" class="form-label">Notes</label>
                <?= $this->Form->textarea('notes', ['id' => 'notes', 'rows' => 3, 'maxlength' => 2000, ]) ?>
            </div>
            <?= $this->Form->button(__('Save Class'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
