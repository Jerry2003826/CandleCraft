<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var int $availableSlots
 * @var array $studentOptions
 * @var int|null $selectedStudentId
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
                    <p class="portal-class-card__eyebrow"><?= h($class->course?->course_name ?? 'Course') ?></p>
                    <h3>Class <?= h($class->class_code) ?></h3>
                </div>
            </div>
            <div class="portal-class-card__meta">
                <span><?= $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : '-' ?> - <?= $class->end_datetime ? $class->end_datetime->format('g:ia') : '-' ?></span>
                <span><?= h($class->location) ?></span>
                <span>Teacher: <?= h($class->teacher?->teacher_name ?? 'TBA') ?></span>
            </div>
            <div class="portal-status-row">
                <div>
                    <span class="portal-status-row__label">Availability</span>
                    <strong><?= h((string)$availableSlots) ?> / <?= h((string)$class->capacity) ?> spots</strong>
                </div>
                <div>
                    <span class="portal-status-row__label">Price</span>
                    <strong>$<?= number_format((float)($class->course?->course_price ?? 0), 2) ?></strong>
                </div>
            </div>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

        <?= $this->Form->create(null, ['url' => ['action' => 'add', $class->class_id]]) ?>
        <fieldset>
            <legend><strong>Choose Child</strong></legend>
            <?= $this->Form->control('student_id', [
                'type' => 'select',
                'label' => 'Book this class for',
                'options' => $studentOptions,
                'value' => $selectedStudentId,
                'required' => true,
            ]) ?>
            <?= $this->Form->control('confirm', [
                'type' => 'checkbox',
                'label' => 'I confirm the booking for the selected child',
                'required' => true,
            ]) ?>
        </fieldset>
        <?= $this->Form->button('Confirm Booking', ['class' => 'btn btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
