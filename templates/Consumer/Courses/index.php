<?php
/**
 * @var \App\View\AppView $this
 * @var array $courseData
 * @var bool $bookingAccessEnabled
 * @var array $calendarEvents
 * @var \Cake\I18n\DateTime $weekStart
 * @var \Cake\I18n\DateTime $weekEnd
 */
$this->assign('title', 'Book a Class');

$prevWeek = $weekStart->modify('-7 days')->format('Y-m-d');
$nextWeek = $weekStart->modify('+7 days')->format('Y-m-d');
$todayWeek = (new \Cake\I18n\DateTime('now'))->modify('-' . date('w') . ' days')->format('Y-m-d');
$todayStr = date('Y-m-d');
$nowHour = (int)date('G');
$nowMinute = (int)date('i');

$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = $weekStart->modify("+{$i} days");
    $weekDays[$i] = [
        'name' => $dayNames[(int)$d->format('w')],
        'date' => (int)$d->format('j'),
        'full' => $d->format('Y-m-d'),
        'dow' => (int)$d->format('w'),
    ];
}

$calHourStart = 8;
$calHourEnd = 22;
$isCurrentWeek = $weekStart->format('Y-m-d') === $todayWeek;
$showCalendar = $this->request->getQuery('week_start') !== null;

// Collect distinct course types for filter pills
$courseTypes = [];
foreach ($courseData as $item) {
    $t = strtolower($item['course']->course_type ?? '');
    if ($t !== '' && !in_array($t, $courseTypes, true)) {
        $courseTypes[] = $t;
    }
}
sort($courseTypes);
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
    <!-- Course type filter -->
    <?php if (!empty($courseTypes)): ?>
    <div class="admin-pill-tabs">
        <button type="button" class="admin-pill-tab sp-filter-btn active" data-filter="all">All</button>
        <?php foreach ($courseTypes as $type): ?>
            <button type="button" class="admin-pill-tab sp-filter-btn" data-filter="<?= h($type) ?>">
                <?= h(ucfirst($type)) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- View controls -->
    <div class="d-flex align-items-center gap-3">
        <!-- Calendar date navigation (shown only in calendar mode) -->
        <div id="calendarNav" style="display:<?= $showCalendar ? 'flex' : 'none' ?>; align-items:center; gap:8px;">
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $prevWeek]]) ?>" class="admin-action-link view" aria-label="Show previous week"><i class="bi bi-chevron-left"></i></a>
            <?php if (!$isCurrentWeek): ?>
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $todayWeek]]) ?>" class="admin-pill-tab" style="text-decoration:none;">Today</a>
            <?php endif; ?>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $nextWeek]]) ?>" class="admin-action-link view" aria-label="Show next week"><i class="bi bi-chevron-right"></i></a>
            <span style="font-size: 13px; color: var(--admin-text-secondary); font-weight: 500; white-space: nowrap;">
                <?= h($weekStart->format('j M')) ?> - <?= h($weekEnd->format('j M Y')) ?>
            </span>
        </div>

        <!-- List view marker (kept for shared a11y nav helpers; no visible UI) -->
        <div id="listNav" style="display:<?= $showCalendar ? 'none' : 'inline-flex' ?>; align-items:center;"></div>

        <!-- List / Calendar toggle -->
        <div class="admin-pill-tabs" data-view-toggle-managed="custom">
            <button type="button" class="admin-pill-tab sp-view-btn <?= !$showCalendar ? 'active' : '' ?>" data-view="list" aria-pressed="<?= !$showCalendar ? 'true' : 'false' ?>" aria-controls="listView">
                <i class="bi bi-list-ul"></i> List
            </button>
            <button type="button" class="admin-pill-tab sp-view-btn <?= $showCalendar ? 'active' : '' ?>" data-view="calendar" aria-pressed="<?= $showCalendar ? 'true' : 'false' ?>" aria-controls="calendarView">
                <i class="bi bi-calendar-week"></i> Calendar
            </button>
        </div>
    </div>
</div>

<!-- ============ LIST VIEW ============ -->
<div id="listView" tabindex="-1" <?= $showCalendar ? 'hidden' : '' ?>>
    <?php if (empty($courseData)): ?>
        <div class="admin-form-card text-center py-5" style="max-width: 100%;">
            <i class="bi bi-palette" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
            <p class="mt-3" style="color: var(--admin-text-secondary);">No courses available at the moment. Please check back soon.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-4" id="courseList">
            <?php foreach ($courseData as $item):
                $course = $item['course'];
                $classes = $item['classes'];
                $courseBooked = !empty($item['booked_by_current_customer']);
                $courseType = strtolower($course->course_type ?? '');
            ?>
                <div class="cc-course-card" data-course-type="<?= h($courseType) ?>">
                    <div class="admin-form-card" style="max-width: 100%; padding: 28px;">

                        <!-- Course Header -->
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h2 class="admin-form-title m-0" style="font-size: 22px;"><?= h($course->course_name) ?></h2>
                                <?php if ($courseBooked): ?>
                                    <span class="admin-badge admin-badge-success">已订购</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if ($courseType): ?>
                                    <span class="admin-badge admin-badge-info"><?= h(ucfirst($courseType)) ?></span>
                                <?php endif; ?>
                                <?php if ($course->course_level): ?>
                                    <span class="admin-badge admin-badge-neutral"><?= h(ucfirst(str_replace('_', ' ', $course->course_level))) ?></span>
                                <?php endif; ?>
                                <span style="font-size: 15px; font-weight: 600; color: var(--admin-text-primary);">
                                    $<?= number_format((float)$course->course_price, 2) ?> <span style="font-size: 13px; font-weight: 400; color: var(--admin-text-secondary);">per class</span>
                                </span>
                            </div>
                        </div>

                        <?php if ($course->course_description): ?>
                            <p style="font-size: 14px; line-height: 1.65; color: var(--admin-text-secondary); margin: 0 0 20px 0;">
                                <?= h($course->course_description) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Classes -->
                        <?php if (empty($classes)): ?>
                            <div class="p-4 text-center" style="background-color: var(--admin-search-bg); border-radius: 12px; border: 1px dashed var(--admin-card-border);">
                                <p style="color: var(--admin-text-secondary); margin: 0; font-size: 14px;">No upcoming sessions available for this course.</p>
                            </div>
                        <?php else: ?>
                            <div style="border-top: 1px solid var(--admin-card-border); margin-top: 4px;">
                                <?php foreach ($classes as $i => $class):
                                    $isBooked = !empty($class->booked_by_current_customer);
                                    $isFull = $class->available_slots <= 0;
                                    $canBook = $bookingAccessEnabled && !$isFull && !$isBooked;
                                ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 16px 0; <?= $i > 0 ? 'border-top: 1px solid var(--admin-card-border);' : '' ?>">
                                        <div style="min-width: 160px;">
                                            <div style="font-weight: 600; font-size: 14px; color: var(--admin-text-primary); margin-bottom: 2px;">
                                                <?= $class->start_datetime ? $class->start_datetime->format('D, j M Y') : 'Date TBA' ?>
                                            </div>
                                            <div style="font-size: 13px; color: var(--admin-text-secondary);">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= $class->start_datetime ? $class->start_datetime->format('g:ia') : '' ?>
                                                <?= $class->end_datetime ? ' - ' . $class->end_datetime->format('g:ia') : '' ?>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-3 flex-wrap" style="font-size: 13px; color: var(--admin-text-secondary);">
                                            <span><i class="bi bi-person me-1"></i><?= h($class->teacher?->teacher_name ?? 'TBA') ?></span>
                                            <?php if ($class->location): ?>
                                                <span><i class="bi bi-geo-alt me-1"></i><?= h($class->location) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                                            <?php if ($isFull): ?>
                                                <span class="admin-badge admin-badge-danger">Full</span>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-success"><?= h((string)$class->available_slots) ?> spot<?= $class->available_slots !== 1 ? 's' : '' ?> left</span>
                                            <?php endif; ?>

                                            <?php if ($isBooked): ?>
                                                <span class="admin-badge admin-badge-success"><i class="bi bi-check-circle me-1"></i>已订购</span>
                                            <?php elseif ($canBook): ?>
                                                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'add', $class->class_id]) ?>" class="admin-btn-primary" style="padding: 6px 20px; font-size: 13px; white-space: nowrap;">
                                                    Book Now
                                                </a>
                                            <?php elseif (!$bookingAccessEnabled): ?>
                                                <span style="font-size: 13px; color: var(--admin-text-secondary);"><i class="bi bi-lock me-1"></i>Verification required</span>
                                            <?php else: ?>
                                                <span style="font-size: 13px; color: var(--admin-text-secondary);">Unavailable</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div id="noFilterResults" hidden class="admin-form-card text-center py-5" style="max-width: 100%;">
                <i class="bi bi-funnel" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
                <p class="mt-3" style="color: var(--admin-text-secondary);">No courses match the selected filter.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ============ CALENDAR VIEW ============ -->
<div id="calendarView" tabindex="-1" <?= !$showCalendar ? 'hidden' : '' ?>>
    <div class="wc-wrapper">
        <div class="wc-header">
            <div class="wc-gutter-header"></div>
            <?php foreach ($weekDays as $idx => $wd): ?>
                <div class="wc-col-header <?= $wd['full'] === $todayStr ? 'wc-col-header--today' : '' ?>">
                    <span class="wc-col-header__name"><?= $wd['name'] ?></span>
                    <span class="wc-col-header__num <?= $wd['full'] === $todayStr ? 'wc-col-header__num--today' : '' ?>"><?= $wd['date'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="wc-body-grid" style="--wc-rows: <?= $calHourEnd - $calHourStart ?>;">
            <div class="wc-gutter">
                <?php for ($h = $calHourStart; $h < $calHourEnd; $h++): ?>
                    <div class="wc-gutter__label"><?= sprintf('%d:00', $h) ?></div>
                <?php endfor; ?>
            </div>

            <?php for ($d = 0; $d < 7; $d++): ?>
                <div class="wc-day <?= $weekDays[$d]['full'] === $todayStr ? 'wc-day--today' : '' ?>">
                    <?php for ($h = $calHourStart; $h < $calHourEnd; $h++): ?>
                        <div class="wc-hour-line" style="top: calc(<?= ($h - $calHourStart) ?> * var(--wc-hour-h));"></div>
                    <?php endfor; ?>

                    <?php foreach ($calendarEvents as $ev):
                        if ($ev['full_date'] !== $weekDays[$d]['full']) continue;
                        $topMin = ($ev['start_hour'] - $calHourStart) * 60 + $ev['start_minute'];
                        $durMin = ($ev['end_hour'] - $ev['start_hour']) * 60 + ($ev['end_minute'] - $ev['start_minute']);
                        if ($durMin < 30) $durMin = 30;
                        $startFmt = sprintf('%d:%02d', $ev['start_hour'], $ev['start_minute']);
                        $endFmt = sprintf('%d:%02d', $ev['end_hour'], $ev['end_minute']);
                        $evType = strtolower($ev['course_type'] ?? '');
                    ?>
                        <div class="wc-evt" data-course-type="<?= h($evType) ?>" style="top: calc(<?= $topMin ?> * var(--wc-min-h)); height: calc(<?= $durMin ?> * var(--wc-min-h)); --evt-color: <?= h($ev['color']) ?>;">
                            <strong class="wc-evt__title"><?= h($ev['title']) ?></strong>
                            <span class="wc-evt__time"><?= $startFmt ?> - <?= $endFmt ?></span>
                            <span class="wc-evt__loc"><?= h($ev['available_slots']) ?> spots left</span>
                            <?php if (!empty($ev['booked_by_current_customer'])): ?>
                                <span style="font-size: 10px; color: #047857; font-weight: 600; margin-top: 4px;"><i class="bi bi-check-circle"></i> 已订购</span>
                            <?php elseif ($bookingAccessEnabled && $ev['available_slots'] > 0): ?>
                                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'add', $ev['class_id']]) ?>" class="admin-btn-primary" style="padding: 2px 8px; font-size: 11px; width: fit-content; margin-top: 4px;" aria-label="Book <?= h($ev['title']) ?> at <?= h($startFmt) ?>">
                                    Book
                                </a>
                            <?php elseif (!$bookingAccessEnabled): ?>
                                <span style="font-size: 10px; color: var(--admin-text-secondary); margin-top: 4px;">Verification pending</span>
                            <?php else: ?>
                                <span style="font-size: 10px; color: var(--admin-text-secondary); margin-top: 4px;">Full</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($isCurrentWeek && $weekDays[$d]['full'] === $todayStr && $nowHour >= $calHourStart && $nowHour < $calHourEnd): ?>
                        <div class="wc-now-line" id="wcNowLine" style="top: calc(<?= ($nowHour - $calHourStart) * 60 + $nowMinute ?> * var(--wc-min-h));">
                            <span class="wc-now-dot"></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var viewBtns = document.querySelectorAll('.sp-view-btn');
    var filterBtns = document.querySelectorAll('.sp-filter-btn');
    var listView = document.getElementById('listView');
    var calendarView = document.getElementById('calendarView');
    var calendarNav = document.getElementById('calendarNav');
    var noFilterResults = document.getElementById('noFilterResults');
    var activeView = <?= $showCalendar ? "'calendar'" : "'list'" ?>;
    var activeFilter = 'all';

    // --- View toggle ---
    function setView(view, shouldFocus) {
        activeView = view;
        var isCalendar = view === 'calendar';

        viewBtns.forEach(function (btn) {
            var active = btn.getAttribute('data-view') === view;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        listView.hidden = isCalendar;
        calendarView.hidden = !isCalendar;
        if (calendarNav) {
            calendarNav.hidden = !isCalendar;
            calendarNav.style.display = isCalendar ? 'flex' : 'none';
        }

        if (!isCalendar) {
            var url = new URL(window.location);
            url.searchParams.delete('week_start');
            window.history.replaceState({}, '', url);
        }

        if (shouldFocus) {
            (isCalendar ? calendarView : listView).focus();
        }
    }

    viewBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setView(this.getAttribute('data-view'), true);
        });
    });

    // --- Filter ---
    function applyFilter() {
        // List view cards
        var cards = document.querySelectorAll('.cc-course-card');
        var visible = 0;
        cards.forEach(function (card) {
            var type = card.getAttribute('data-course-type');
            var show = activeFilter === 'all' || type === activeFilter;
            card.hidden = !show;
            if (show) visible++;
        });
        if (noFilterResults) noFilterResults.hidden = visible > 0;

        // Calendar events
        document.querySelectorAll('.wc-evt').forEach(function (evt) {
            var type = evt.getAttribute('data-course-type');
            evt.style.visibility = (activeFilter === 'all' || type === activeFilter) ? '' : 'hidden';
        });
    }

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeFilter = this.getAttribute('data-filter');
            filterBtns.forEach(function (b) {
                b.classList.toggle('active', b.getAttribute('data-filter') === activeFilter);
            });
            applyFilter();
        });
    });

    setView(activeView, false);
});
</script>
