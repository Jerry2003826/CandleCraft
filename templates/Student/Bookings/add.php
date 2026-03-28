<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \App\Model\Entity\Student $student
 * @var int $availableSlots
 */
$this->assign('title', 'Book Class');
?>

<div class="card">
    <div class="card-header">
        <h3>Book Class: <?= h($class->class_code) ?></h3>
        <a href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Courses', 'action' => 'view', $class->course_id]) ?>" class="btn btn-sm">&larr; Back</a>
    </div>
    <div class="card-body">
        <div class="portal-class-card">
            <div class="portal-class-card__header">
                <div>
                    <p class="portal-class-card__eyebrow"><?= h($class->course ? $class->course->course_name : 'Course') ?></p>
                    <h3>Class <?= h($class->class_code) ?></h3>
                </div>
            </div>

            <div class="portal-class-card__meta">
                <span>
                    <?= $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : '-' ?>
                    -
                    <?= $class->end_datetime ? $class->end_datetime->format('g:ia') : '-' ?>
                </span>
                <span><?= h($class->location) ?></span>
                <span>Teacher: <?= h($class->teacher ? $class->teacher->teacher_name : 'TBA') ?></span>
            </div>

            <div class="portal-status-row">
                <div>
                    <span class="portal-status-row__label">Capacity</span>
                    <strong><?= $availableSlots ?> / <?= $class->capacity ?> spots available</strong>
                </div>
                <div>
                    <span class="portal-status-row__label">Price</span>
                    <strong>$<?= number_format((float)($class->course ? $class->course->course_price : 0), 2) ?></strong>
                </div>
            </div>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

        <?= $this->Form->create(null, ['url' => ['action' => 'add', $class->class_id]]) ?>
        <fieldset>
            <legend><strong>Confirm Your Booking</strong></legend>
            <p style="color: #666;">You are booking as <strong><?= h($student->student_name) ?></strong>.</p>

            <?= $this->Form->control('confirm', [
                'type' => 'checkbox',
                'label' => 'I confirm I want to book this class',
                'required' => true,
            ]) ?>
        </fieldset>

        <?= $this->Form->button('Confirm Booking', ['class' => 'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
