<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \App\Model\Entity\Student $student
 * @var int $availableSlots
 */
$this->assign('title', 'Book Class');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Courses</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Book Class: <?= h($class->class_code) ?></h5>
    </div>
    <div class="card-body">
        <div class="portal-class-card mb-4">
            <div class="portal-class-card__header">
                <div>
                    <p class="portal-class-card__eyebrow"><?= h($class->course ? $class->course->course_name : 'Course') ?></p>
                    <h3>Class <?= h($class->class_code) ?></h3>
                </div>
            </div>

            <div class="portal-class-card__meta">
                <span><i class="bi bi-clock me-1"></i><?= $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : '-' ?> - <?= $class->end_datetime ? $class->end_datetime->format('g:ia') : '-' ?></span>
                <span><i class="bi bi-geo-alt me-1"></i><?= h($class->location) ?></span>
                <span><i class="bi bi-person me-1"></i><?= h($class->teacher ? $class->teacher->teacher_name : 'TBA') ?></span>
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

        <hr>

        <?= $this->Form->create(null, ['url' => ['action' => 'add', $class->class_id]]) ?>
        <fieldset>
            <legend class="h6">Confirm Your Booking</legend>
            <p class="text-muted">You are booking as <strong><?= h($student->student_name) ?></strong>.</p>

            <?= $this->Form->control('confirm', [
                'type' => 'checkbox',
                'label' => 'I confirm I want to book this class',
                'required' => true,
            ]) ?>
        </fieldset>

        <div class="d-flex gap-2 mt-3">
            <?= $this->Form->button('<i class="bi bi-check-circle me-1"></i> Confirm Booking', ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
            <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
