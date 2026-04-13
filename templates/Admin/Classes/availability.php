<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, array<\App\Model\Entity\ClassEntity>> $classesByDay
 * @var \DateTimeImmutable[] $days
 * @var \Cake\ORM\ResultSet $courses
 * @var \Cake\ORM\ResultSet $teachers
 * @var int $weekOffset
 */
$this->assign('title', 'Classes');
$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$today = (new \DateTimeImmutable())->format('Y-m-d');
$weekLabel = $days[0]->format('j M') . ' – ' . $days[6]->format('j M Y');
?>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->Url->build(['action' => 'index']) ?>">Class List</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?= $this->Url->build(['action' => 'availability']) ?>">Availability</a>
    </li>
</ul>

<!-- Week Toolbar -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h5 class="mb-0 fw-bold">Week of <?= $weekLabel ?></h5>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $this->Url->build(['action' => 'availability', '?' => ['week' => $weekOffset - 1]]) ?>" class="btn btn-sm btn-outline-secondary">&larr; Prev</a>
        <a href="<?= $this->Url->build(['action' => 'availability']) ?>" class="btn btn-sm btn-primary">Today</a>
        <a href="<?= $this->Url->build(['action' => 'availability', '?' => ['week' => $weekOffset + 1]]) ?>" class="btn btn-sm btn-outline-secondary">Next &rarr;</a>
        <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#addSlotModal">
            <i class="bi bi-plus-lg"></i> Add Time Slot
        </button>
    </div>
</div>

<!-- Weekly Calendar Grid -->
<div class="card">
    <div class="avail-grid">
        <?php foreach ($days as $i => $day):
            $dayKey = $day->format('Y-m-d');
            $isToday = $dayKey === $today;
            $slotsForDay = $classesByDay[$dayKey] ?? [];
        ?>
        <div class="avail-day <?= $isToday ? 'avail-day--today' : '' ?>">
            <div class="avail-day__header <?= $isToday ? 'avail-day__header--today' : '' ?>">
                <span class="avail-day__name"><?= $dayNames[$i] ?></span>
                <span class="avail-day__num"><?= $day->format('j') ?></span>
            </div>
            <div class="avail-day__body">
                <?php if (empty($slotsForDay)): ?>
                    <div class="avail-empty">No classes</div>
                <?php else: ?>
                    <?php foreach ($slotsForDay as $class): ?>
                        <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="avail-slot">
                            <span class="avail-slot__time">
                                <?= $class->start_datetime->format('H:i') ?> – <?= $class->end_datetime->format('H:i') ?>
                            </span>
                            <span class="avail-slot__title"><?= h($class->course ? $class->course->course_name : $class->class_code) ?></span>
                            <span class="avail-slot__teacher"><?= h($class->teacher ? $class->teacher->teacher_name : '-') ?></span>
                            <span class="avail-slot__spots">
                                <?= count($class->bookings ?? []) ?>/<?= h($class->capacity) ?> spots
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Time Slot Modal -->
<div class="modal fade" id="addSlotModal" tabindex="-1" aria-labelledby="addSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <?= $this->Form->create(null, ['url' => ['action' => 'add']]) ?>
            <div class="modal-header">
                <h5 class="modal-title" id="addSlotModalLabel">Add Time Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Create a new class slot for students to book.</p>

                <div class="mb-3">
                    <label for="slot-course" class="form-label">Course <span class="text-danger">*</span></label>
                    <?= $this->Form->select('course_id', $courses, ['id' => 'slot-course', 'empty' => 'Select a course...', 'required' => true]) ?>
                </div>

                <div class="mb-3">
                    <label for="slot-code" class="form-label">Class Code <span class="text-danger">*</span></label>
                    <?= $this->Form->text('class_code', ['id' => 'slot-code', 'required' => true, 'placeholder' => 'e.g. POT-BEG-001', 'maxlength' => 30, 'pattern' => '[A-Za-z0-9-]{3,30}']) ?>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="slot-date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="slot-date" required
                               value="<?= $days[0]->format('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="slot-start" class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="slot-start" required value="10:00">
                    </div>
                    <div class="col-md-4">
                        <label for="slot-end" class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="slot-end" required value="12:00">
                    </div>
                </div>

                <input type="hidden" name="start_datetime" id="slot-start-datetime">
                <input type="hidden" name="end_datetime" id="slot-end-datetime">

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="slot-teacher" class="form-label">Teacher <span class="text-danger">*</span></label>
                        <?= $this->Form->select('teacher_id', $teachers, ['id' => 'slot-teacher', 'empty' => 'Select teacher...', 'required' => true]) ?>
                    </div>
                    <div class="col-md-6">
                        <label for="slot-capacity" class="form-label">Max Capacity <span class="text-danger">*</span></label>
                        <?= $this->Form->number('capacity', ['id' => 'slot-capacity', 'value' => 15, 'min' => 1, 'max' => 200]) ?>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="slot-location" class="form-label">Location</label>
                    <?= $this->Form->text('location', ['id' => 'slot-location', 'placeholder' => 'Studio A', 'value' => 'Studio A']) ?>
                </div>

                <?= $this->Form->hidden('class_status', ['value' => 'scheduled']) ?>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="slot-repeat" checked>
                    <label class="form-check-label" for="slot-repeat">
                        Repeat weekly for <select id="slot-repeat-weeks" class="form-select form-select-sm d-inline-block" style="width:auto">
                            <?php for ($w = 1; $w <= 12; $w++): ?>
                                <option value="<?= $w ?>" <?= $w === 8 ? 'selected' : '' ?>><?= $w ?></option>
                            <?php endfor; ?>
                        </select> weeks
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="slot-submit">Create Slot</button>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?php $this->Html->scriptBlock("
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('#addSlotModal form');
    if (!form) return;
    form.addEventListener('submit', function() {
        var d = document.getElementById('slot-date').value;
        var s = document.getElementById('slot-start').value;
        var e = document.getElementById('slot-end').value;
        document.getElementById('slot-start-datetime').value = d + 'T' + s;
        document.getElementById('slot-end-datetime').value = d + 'T' + e;
    });
});
", ['block' => true]); ?>
