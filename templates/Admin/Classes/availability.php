<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, array<\App\Model\Entity\ClassEntity>> $classesByDay
 * @var \DateTimeImmutable[] $days
 * @var \Cake\ORM\ResultSet $courses
 * @var \Cake\ORM\ResultSet $teachers
 * @var \Cake\ORM\ResultSet $allCourses
 * @var array<int, bool> $scheduledCourseIds
 * @var int $weekOffset
 */
$this->assign('title', 'Classes');
$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$today = (new \DateTimeImmutable())->format('Y-m-d');
$weekLabel = $days[0]->format('j M') . ' – ' . $days[6]->format('j M Y');
$totalSlots = 0;
$calendarEvents = [];
$earliestMinutes = null;
$latestMinutes = null;
foreach ($classesByDay as $daySlots) {
    $totalSlots += count($daySlots);
}

foreach ($days as $day) {
    $dayKey = $day->format('Y-m-d');
    foreach (($classesByDay[$dayKey] ?? []) as $class) {
        if (!$class->start_datetime || !$class->end_datetime) {
            continue;
        }

        $courseType = strtolower((string)($class->course->course_type ?? 'default'));
        $eventColor = match ($courseType) {
            'pottery' => '#3B82F6',
            'knitting' => '#F59E0B',
            default => '#6B7280',
        };
        $eventBackground = match ($courseType) {
            'pottery' => 'rgba(59, 130, 246, 0.16)',
            'knitting' => 'rgba(245, 158, 11, 0.16)',
            default => 'rgba(107, 114, 128, 0.16)',
        };

        $startHour = (int)$class->start_datetime->format('G');
        $startMinute = (int)$class->start_datetime->format('i');
        $endHour = (int)$class->end_datetime->format('G');
        $endMinute = (int)$class->end_datetime->format('i');
        $startTotalMinutes = ($startHour * 60) + $startMinute;
        $endTotalMinutes = ($endHour * 60) + $endMinute;

        $earliestMinutes = $earliestMinutes === null ? $startTotalMinutes : min($earliestMinutes, $startTotalMinutes);
        $latestMinutes = $latestMinutes === null ? $endTotalMinutes : max($latestMinutes, $endTotalMinutes);

        $calendarEvents[] = [
            'class_id' => $class->class_id,
            'full_date' => $dayKey,
            'title' => (string)($class->course ? $class->course->course_name : $class->class_code),
            'class_code' => (string)$class->class_code,
            'teacher' => (string)($class->teacher ? $class->teacher->teacher_name : '-'),
            'location' => (string)($class->location ?? ''),
            'spots' => count($class->bookings ?? []) . '/' . (int)$class->capacity . ' spots',
            'start_hour' => $startHour,
            'start_minute' => $startMinute,
            'end_hour' => $endHour,
            'end_minute' => $endMinute,
            'color' => $eventColor,
            'background' => $eventBackground,
        ];
    }
}

if ($earliestMinutes === null || $latestMinutes === null) {
    $calHourStart = 8;
    $calHourEnd = 20;
} else {
    $calHourStart = max(6, (int)floor(($earliestMinutes - 60) / 60));
    $calHourEnd = min(22, (int)ceil(($latestMinutes + 60) / 60));
    if ($calHourEnd <= $calHourStart) {
        $calHourEnd = min(22, $calHourStart + 1);
    }
}

$currentWeekStart = (new \DateTimeImmutable('monday this week'))->format('Y-m-d');
$isCurrentWeek = $days[0]->format('Y-m-d') === $currentWeekStart;
$nowHour = (int)date('G');
$nowMinute = (int)date('i');
?>

<!-- Tab Navigation -->
<div class="admin-page-header mb-4">
    <div class="admin-tabs">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab">Class List</a>
        <a href="<?= $this->Url->build(['action' => 'availability']) ?>" class="admin-tab active">Availability</a>
        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Attendance', 'action' => 'index']) ?>" class="admin-tab">Attendance</a>
    </div>
</div>

<!-- Week Toolbar -->
<div class="admin-page-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $this->Url->build(['action' => 'availability', '?' => ['week' => $weekOffset - 1]]) ?>" class="admin-action-link view"><i class="bi bi-chevron-left"></i></a>
            <a href="<?= $this->Url->build(['action' => 'availability']) ?>" class="admin-tab" style="padding: 4px 12px; font-size: 13px;">Today</a>
            <a href="<?= $this->Url->build(['action' => 'availability', '?' => ['week' => $weekOffset + 1]]) ?>" class="admin-action-link view"><i class="bi bi-chevron-right"></i></a>
        </div>
        <h2 class="admin-form-title m-0" style="font-size: 16px;">Week of <?= $weekLabel ?></h2>
    </div>
    
    <button type="button" class="admin-btn-primary" data-bs-toggle="modal" data-bs-target="#addSlotModal">
        <i class="bi bi-plus-lg"></i> Add Time Slot
    </button>
</div>

<!-- Weekly Calendar Grid -->
<div class="wc-wrapper admin-availability-calendar">
    <div class="wc-header">
        <div class="wc-gutter-header"></div>
        <?php foreach ($days as $i => $day):
            $dayKey = $day->format('Y-m-d');
            $isToday = $dayKey === $today;
        ?>
            <div class="wc-col-header <?= $isToday ? 'wc-col-header--today' : '' ?>">
                <span class="wc-col-header__name"><?= $dayNames[$i] ?></span>
                <span class="wc-col-header__num <?= $isToday ? 'wc-col-header__num--today' : '' ?>"><?= $day->format('j') ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="wc-scroll" id="adminAvailabilityScroll">
        <div class="wc-body-grid" style="--wc-rows: <?= $calHourEnd - $calHourStart ?>;">
            <div class="wc-gutter">
                <?php for ($h = $calHourStart; $h < $calHourEnd; $h++): ?>
                    <div class="wc-gutter__label" style="top: calc(<?= ($h - $calHourStart) ?> * var(--wc-hour-h));">
                        <?= sprintf('%02d:00', $h) ?>
                    </div>
                <?php endfor; ?>
            </div>

            <?php foreach ($days as $day): ?>
                <?php
                    $dayKey = $day->format('Y-m-d');
                    $isToday = $dayKey === $today;
                    $slotsForDay = $classesByDay[$dayKey] ?? [];
                ?>
                <div class="wc-day <?= $isToday ? 'wc-day--today' : '' ?>">
                    <?php for ($h = $calHourStart; $h < $calHourEnd; $h++): ?>
                        <div class="wc-hour-line" style="top: calc(<?= ($h - $calHourStart) ?> * var(--wc-hour-h));"></div>
                    <?php endfor; ?>

                    <?php if (empty($slotsForDay)): ?>
                        <div class="admin-availability-empty-day">No classes</div>
                    <?php endif; ?>

                    <?php foreach ($calendarEvents as $event): ?>
                        <?php
                            if ($event['full_date'] !== $dayKey) {
                                continue;
                            }
                            $topMin = ($event['start_hour'] - $calHourStart) * 60 + $event['start_minute'];
                            $durMin = ($event['end_hour'] - $event['start_hour']) * 60 + ($event['end_minute'] - $event['start_minute']);
                            if ($durMin < 30) {
                                $durMin = 30;
                            }
                            $startFmt = sprintf('%02d:%02d', $event['start_hour'], $event['start_minute']);
                            $endFmt = sprintf('%02d:%02d', $event['end_hour'], $event['end_minute']);
                        ?>
                        <a
                            href="<?= $this->Url->build(['action' => 'edit', $event['class_id']]) ?>"
                            class="wc-evt"
                            style="top: calc(<?= $topMin ?> * var(--wc-min-h)); height: calc(<?= $durMin ?> * var(--wc-min-h)); --evt-color: <?= h($event['color']) ?>; background-color: <?= h($event['background']) ?>;"
                            title="<?= h($event['title']) ?>"
                        >
                            <strong class="wc-evt__time"><?= $startFmt ?> – <?= $endFmt ?></strong>
                            <strong class="wc-evt__title"><?= h($event['title']) ?></strong>
                            <span class="wc-evt__loc"><?= h($event['teacher']) ?></span>
                            <span class="wc-evt__loc"><?= h($event['class_code']) ?><?= $event['location'] !== '' ? ' · ' . h($event['location']) : '' ?></span>
                            <span class="wc-evt__loc"><?= h($event['spots']) ?></span>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($isCurrentWeek && $isToday && $nowHour >= $calHourStart && $nowHour < $calHourEnd): ?>
                        <div class="wc-now-line" style="top: calc(<?= (($nowHour - $calHourStart) * 60) + $nowMinute ?> * var(--wc-min-h));">
                            <span class="wc-now-dot"></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (empty($calendarEvents)): ?>
    <div class="text-center py-4 text-muted">
        <p class="mb-0">No classes scheduled this week.</p>
    </div>
<?php endif; ?>

<!-- Course Summary Panel -->
<div class="admin-form-card mt-4" style="max-width: 100%; padding: 0;">
    <div class="admin-form-header d-flex align-items-center justify-content-between m-0 px-4 py-3" style="border-bottom: 1px solid var(--admin-card-border);">
        <h6 class="mb-0 fw-bold admin-form-title" style="font-size: 16px;"><i class="bi bi-journal-bookmark me-2"></i> All Available Courses</h6>
        <span class="admin-badge admin-badge-neutral"><?= count($allCourses) ?> courses / <?= $totalSlots ?> scheduled this week</span>
    </div>
    <div class="p-0">
        <div class="avail-course-grid" style="background: transparent;">
            <?php foreach ($allCourses as $course):
                $courseType = strtolower($course->course_type ?? 'default');
                $isScheduled = isset($scheduledCourseIds[$course->course_id]);
                $colorClass = match($courseType) {
                    'pottery' => 'avail-course-card--pottery',
                    'knitting' => 'avail-course-card--knitting',
                    default => 'avail-course-card--default',
                };
            ?>
            <div class="avail-course-card <?= $colorClass ?> <?= $isScheduled ? '' : 'avail-course-card--unscheduled' ?>">
                <div class="avail-course-card__bar"></div>
                <div class="avail-course-card__body">
                    <div class="avail-course-card__name"><?= h($course->course_name) ?></div>
                    <div class="avail-course-card__meta">
                        <span class="avail-course-card__type"><?= h(ucfirst($course->course_type)) ?></span>
                        <span class="avail-course-card__level"><?= h(ucfirst($course->course_level)) ?></span>
                        <span class="avail-course-card__price">$<?= number_format($course->course_price, 2) ?></span>
                    </div>
                    <div class="avail-course-card__status mt-3">
                        <?php if ($isScheduled): ?>
                            <span class="admin-badge admin-badge-success" style="font-size: 11px;"><i class="bi bi-check-circle me-1"></i> Scheduled</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-warning" style="font-size: 11px;"><i class="bi bi-exclamation-circle me-1"></i> Not scheduled this week</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isScheduled): ?>
                    <button type="button"
                            class="admin-btn-secondary mt-3 avail-quick-add"
                            data-bs-toggle="modal"
                            data-bs-target="#addSlotModal"
                            data-course-id="<?= $course->course_id ?>"
                            data-course-name="<?= h($course->course_name) ?>"
                            style="width: 100%;">
                        <i class="bi bi-plus-lg me-1"></i> Schedule Now
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Time Slot Modal -->
<div class="modal fade" id="addSlotModal" tabindex="-1" aria-labelledby="addSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 16px;">
            <?= $this->Form->create(null, ['url' => ['action' => 'add']]) ?>
            <div class="modal-header" style="border-bottom: 1px solid var(--admin-card-border);">
                <h5 class="modal-title admin-form-title" id="addSlotModalLabel">Add Time Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--bs-btn-close-filter);"></button>
            </div>
            <div class="modal-body p-4">
                <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 24px;">Create a new class slot for students to book.</p>

                <div class="admin-form-group">
                    <label for="slot-course" class="admin-form-label">Course <span class="text-danger">*</span></label>
                    <?= $this->Form->select('course_id', $courses, [
                        'id' => 'slot-course', 
                        'empty' => 'Select a course...', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>

                <div class="admin-form-group">
                    <label for="slot-code" class="admin-form-label">Class Code <span class="text-danger">*</span></label>
                    <?= $this->Form->text('class_code', [
                        'id' => 'slot-code', 
                        'required' => true, 
                        'placeholder' => 'e.g. POT-BEG-001', 
                        'maxlength' => 30, 
                        'pattern' => '[A-Za-z0-9-]{3,30}',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="admin-form-group mb-0">
                            <label for="slot-start-dt" class="admin-form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <?= $this->Form->text('start_datetime', [
                                'type' => 'datetime-local',
                                'id' => 'slot-start-dt',
                                'required' => true,
                                'value' => $days[0]->format('Y-m-d') . 'T10:00',
                                'class' => 'admin-form-input'
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-form-group mb-0">
                            <label for="slot-end-dt" class="admin-form-label">End Date & Time <span class="text-danger">*</span></label>
                            <?= $this->Form->text('end_datetime', [
                                'type' => 'datetime-local',
                                'id' => 'slot-end-dt',
                                'required' => true,
                                'value' => $days[0]->format('Y-m-d') . 'T12:00',
                                'class' => 'admin-form-input'
                            ]) ?>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="admin-form-group mb-0">
                            <label for="slot-teacher" class="admin-form-label">Teacher <span class="text-danger">*</span></label>
                            <?= $this->Form->select('teacher_id', $teachers, [
                                'id' => 'slot-teacher', 
                                'empty' => 'Select teacher...', 
                                'required' => true,
                                'class' => 'admin-form-select'
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-form-group mb-0">
                            <label for="slot-capacity" class="admin-form-label">Max Capacity <span class="text-danger">*</span></label>
                            <?= $this->Form->number('capacity', [
                                'id' => 'slot-capacity', 
                                'value' => 15, 
                                'min' => 1, 
                                'max' => 200,
                                'class' => 'admin-form-input'
                            ]) ?>
                        </div>
                    </div>
                </div>

                <div class="admin-form-group mt-4">
                    <label for="slot-location" class="admin-form-label">Location <span class="text-danger">*</span></label>
                    <?= $this->Form->text('location', [
                        'id' => 'slot-location', 
                        'placeholder' => 'Studio A', 
                        'value' => 'Studio A', 
                        'required' => true,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>

                <?= $this->Form->hidden('class_status', ['value' => 'scheduled']) ?>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--admin-card-border);">
                <button type="button" class="admin-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <?= $this->Form->button('Create Slot', ['class' => 'admin-btn-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.avail-quick-add').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var courseId = this.getAttribute('data-course-id');
        var courseSelect = document.getElementById('slot-course');
        if (courseSelect && courseId) {
            courseSelect.value = courseId;
        }
    });
});
</script>
