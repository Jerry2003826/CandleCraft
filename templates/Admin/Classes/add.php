<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \Cake\ORM\ResultSet $courses
 * @var \Cake\ORM\ResultSet $teachers
 */
$this->assign('title', 'Add Class');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Classes</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Add Class</h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create($class) ?>
            <div class="form-group">
                <label for="class-code">Class Code</label>
                <?= $this->Form->text('class_code', [
                    'id' => 'class-code',
                    'required' => true,
                    'placeholder' => 'e.g. POT-BEG-001',
                    'maxlength' => 30,
                    'pattern' => '[A-Za-z0-9-]{3,30}',
                ]) ?>
            </div>
            <div class="form-group">
                <label for="course-id">Course</label>
                <?= $this->Form->select('course_id', $courses, [
                    'id' => 'course-id',
                    'empty' => '-- Select Course --',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="teacher-id">Teacher</label>
                <?= $this->Form->select('teacher_id', $teachers, [
                    'id' => 'teacher-id',
                    'empty' => '-- Select Teacher --',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="start-datetime">Start Date & Time</label>
                <?= $this->Form->text('start_datetime', [
                    'type' => 'datetime-local',
                    'id' => 'start-datetime',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="end-datetime">End Date & Time</label>
                <?= $this->Form->text('end_datetime', [
                    'type' => 'datetime-local',
                    'id' => 'end-datetime',
                    'required' => true,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <?= $this->Form->text('location', [
                    'id' => 'location',
                    'required' => true,
                    'placeholder' => 'e.g. Studio A',
                    'maxlength' => 150,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="capacity">Capacity</label>
                <?= $this->Form->number('capacity', [
                    'id' => 'capacity',
                    'value' => 20,
                    'min' => 1,
                    'max' => 200,
                ]) ?>
            </div>
            <div class="form-group">
                <label for="class-status">Status</label>
                <?= $this->Form->select('class_status', [
                    'scheduled' => 'Scheduled',
                    'ongoing' => 'Ongoing',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'full' => 'Full',
                ], ['id' => 'class-status', 'default' => 'scheduled']) ?>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <?= $this->Form->textarea('notes', [
                    'id' => 'notes',
                    'rows' => 3,
                    'maxlength' => 2000,
                ]) ?>
            </div>
            <?= $this->Form->button(__('Save Class'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn" style="margin-left: 8px;">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
