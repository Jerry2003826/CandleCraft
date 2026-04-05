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

<div class="mb-3">
    <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Courses</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Book Class: <?= h($class->class_code) ?></h5>
    </div>
    <div class="card-body">
        <div class="portal-class-card mb-4">
            <div class="portal-class-card__header">
                <div>
                    <p class="portal-class-card__eyebrow"><?= h($class->course?->course_name ?? 'Course') ?></p>
                    <h3>Class <?= h($class->class_code) ?></h3>
                </div>
            </div>
            <div class="portal-class-card__meta">
                <span><i class="bi bi-clock me-1"></i><?= $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : '-' ?> - <?= $class->end_datetime ? $class->end_datetime->format('g:ia') : '-' ?></span>
                <span><i class="bi bi-geo-alt me-1"></i><?= h($class->location) ?></span>
                <span><i class="bi bi-person me-1"></i><?= h($class->teacher?->teacher_name ?? 'TBA') ?></span>
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

        <hr>

        <?= $this->Form->create(null, ['url' => ['action' => 'add', $class->class_id]]) ?>
        <fieldset>
            <legend class="h6">Choose Child</legend>
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
        <div class="d-flex gap-2 mt-3">
            <?= $this->Form->button('<i class="bi bi-check-circle me-1"></i> Confirm Booking', ['class' => 'btn btn-primary', 'escape' => false]) ?>
            <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
