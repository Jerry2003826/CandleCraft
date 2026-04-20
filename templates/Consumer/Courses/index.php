<?php
/**
 * @var \App\View\AppView $this
 * @var array $courseData
 * @var bool $bookingAccessEnabled
 * @var array $calendarEvents
 * @var \Cake\I18n\DateTime $weekStart
 * @var \Cake\I18n\DateTime $weekEnd
 */
$this->assign('title', 'Booking System');

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

// Check if we should show calendar by default (if week_start is set in URL)
$showCalendar = $this->request->getQuery('week_start') !== null;
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4" data-view-toggle-managed="custom">
    <!-- Left: Date Nav (Calendar Only) or Title (List Only) -->
    <div id="calendarNav" class="d-flex align-items-center gap-3" <?= $showCalendar ? '' : 'hidden' ?>>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $prevWeek]]) ?>" class="admin-action-link view" aria-label="Show previous week"><i class="bi bi-chevron-left"></i></a>
            <?php if (!$isCurrentWeek): ?>
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $todayWeek]]) ?>" class="admin-tab" style="padding: 4px 12px; font-size: 13px;">Today</a>
            <?php endif; ?>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $nextWeek]]) ?>" class="admin-action-link view" aria-label="Show next week"><i class="bi bi-chevron-right"></i></a>
        </div>
        <h2 class="admin-form-title m-0" style="font-size: 16px;"><?= h($weekStart->format('M j')) ?> — <?= h($weekEnd->format('M j, Y')) ?></h2>
    </div>
    
    <div id="listNav" <?= $showCalendar ? 'hidden' : '' ?>>
        <h2 class="admin-form-title m-0" style="font-size: 18px;">Available Classes</h2>
    </div>

    <!-- Right: View Toggle -->
    <div class="admin-tabs">
        <button type="button" class="admin-tab sp-view-btn <?= !$showCalendar ? 'active' : '' ?>" data-view="list" aria-pressed="<?= !$showCalendar ? 'true' : 'false' ?>" aria-controls="listView">
            <i class="bi bi-list-ul"></i> List
        </button>
        <button type="button" class="admin-tab sp-view-btn <?= $showCalendar ? 'active' : '' ?>" data-view="calendar" aria-pressed="<?= $showCalendar ? 'true' : 'false' ?>" aria-controls="calendarView">
            <i class="bi bi-calendar-week"></i> Calendar
        </button>
    </div>
</div>

<!-- ============ LIST VIEW ============ -->
<div id="listView" tabindex="-1" <?= $showCalendar ? 'hidden' : '' ?>>
    <?php if (empty($courseData)): ?>
        <div class="admin-form-card text-center py-5" style="max-width: 100%;">
            <i class="bi bi-palette text-muted" style="font-size: 48px;"></i>
            <p class="mt-3 text-muted">No courses available at the moment. Please check back soon.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($courseData as $item):
                $course = $item['course'];
                $classes = $item['classes'];
                $courseType = strtolower($course->course_type ?? 'default');
                $typeColor = $courseType === 'pottery' ? '#1D4ED8' : ($courseType === 'knitting' ? '#B45309' : '#374151');
                $typeBg = $courseType === 'pottery' ? '#DBEAFE' : ($courseType === 'knitting' ? '#FEF3C7' : '#F3F4F6');
            ?>
                <div class="col-12 rd-course-card-wrapper" data-course-name="<?= h(strtolower($course->course_name ?? '')) ?>">
                    <div class="admin-form-card" style="max-width: 100%; padding: 32px;">
                        <!-- Course Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
                            <div>
                                <h2 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 24px; color: var(--admin-text-primary); margin: 0 0 8px 0;">
                                    <?= h($course->course_name) ?>
                                </h2>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <span style="background-color: <?= $typeBg ?>; color: <?= $typeColor ?>; padding: 4px 10px; border-radius: 12px; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px;">
                                        <?= h(ucfirst($courseType)) ?>
                                    </span>
                                    <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                                        <?= h(ucfirst(str_replace('_', ' ', $course->course_level ?? 'All Levels'))) ?>
                                    </span>
                                    <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                                        <i class="bi bi-tag me-1"></i>$<?= number_format((float)$course->course_price, 2) ?> per class
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($course->course_description): ?>
                            <p style="font-family: 'Inter', sans-serif; font-size: 15px; line-height: 1.6; color: var(--admin-text-primary); margin: 16px 0 24px 0;">
                                <?= h($course->course_description) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Classes List -->
                        <h3 class="admin-form-title mb-3" style="font-size: 16px;">Available Classes</h3>
                        
                        <?php if (empty($classes)): ?>
                            <div class="p-4 text-center" style="background-color: var(--admin-search-bg); border-radius: 12px; border: 1px dashed var(--admin-card-border);">
                                <p style="color: var(--admin-text-secondary); margin: 0; font-family: 'Inter', sans-serif; font-size: 14px;">No upcoming classes available for this course.</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($classes as $class): ?>
                                    <div style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 20px; transition: border-color 0.2s;">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                            
                                            <!-- Class Info -->
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.05em;">
                                                        <?= h($class->class_code) ?>
                                                    </span>
                                                    <?php if ($class->available_slots <= 0): ?>
                                                        <span class="admin-badge admin-badge-danger">Full</span>
                                                    <?php else: ?>
                                                        <span class="admin-badge admin-badge-success"><?= h((string)$class->available_slots) ?> spot<?= $class->available_slots !== 1 ? 's' : '' ?> left</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <h4 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 18px; color: var(--admin-text-primary); margin: 0 0 12px 0;">
                                                    <?= $class->start_datetime ? $class->start_datetime->format('D, j M Y') : 'Date TBA' ?>
                                                </h4>
                                                
                                                <div class="d-flex flex-wrap gap-4" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                                                    <span class="d-flex align-items-center gap-1">
                                                        <i class="bi bi-clock"></i>
                                                        <?= $class->start_datetime ? $class->start_datetime->format('g:ia') : '' ?>
                                                        <?= $class->end_datetime ? ' – ' . $class->end_datetime->format('g:ia') : '' ?>
                                                    </span>
                                                    <?php if ($class->location): ?>
                                                        <span class="d-flex align-items-center gap-1">
                                                            <i class="bi bi-geo-alt"></i><?= h($class->location) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="d-flex align-items-center gap-1">
                                                        <i class="bi bi-person"></i><?= h($class->teacher?->teacher_name ?? 'TBA') ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Booking Action -->
                                            <div class="text-end">
                                                <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 20px; color: var(--admin-text-primary); margin-bottom: 12px;">
                                                    $<?= number_format((float)$course->course_price, 2) ?>
                                                </div>
                                                
                                                <?php if ($bookingAccessEnabled && $class->available_slots > 0): ?>
                                                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'add', $class->class_id]) ?>" class="admin-btn-primary" style="padding: 8px 24px;">
                                                        Book Now
                                                    </a>
                                                <?php elseif (!$bookingAccessEnabled): ?>
                                                    <div style="padding: 8px 16px; background-color: var(--admin-search-bg); border-radius: 8px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                                        <i class="bi bi-lock"></i> Awaiting adult verification
                                                    </div>
                                                <?php else: ?>
                                                    <div style="padding: 8px 16px; background-color: var(--admin-search-bg); border-radius: 8px; color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                                        Class Full
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
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
                        ?>
                            <div class="wc-evt" style="top: calc(<?= $topMin ?> * var(--wc-min-h)); height: calc(<?= $durMin ?> * var(--wc-min-h)); --evt-color: <?= h($ev['color']) ?>;">
                                <strong class="wc-evt__title"><?= h($ev['title']) ?></strong>
                                <span class="wc-evt__time"><?= $startFmt ?> – <?= $endFmt ?></span>
                                <span class="wc-evt__loc"><?= h($ev['available_slots']) ?> spots left</span>
                                <?php if ($bookingAccessEnabled && $ev['available_slots'] > 0): ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'add', $ev['class_id']]) ?>" class="admin-btn-primary" style="padding: 2px 8px; font-size: 11px; width: fit-content; margin-top: 4px;" aria-label="Book <?= h($ev['title']) ?> at <?= h($startFmt) ?>">
                                        Book
                                    </a>
                                <?php elseif (!$bookingAccessEnabled): ?>
                                    <span style="font-size: 10px; color: #EF4444; margin-top: 4px;">Verification pending</span>
                                <?php else: ?>
                                    <span style="font-size: 10px; color: #EF4444; margin-top: 4px;">Full</span>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewBtns = document.querySelectorAll('.sp-view-btn');
    var listView = document.getElementById('listView');
    var calendarView = document.getElementById('calendarView');
    var calendarNav = document.getElementById('calendarNav');
    var listNav = document.getElementById('listNav');
    var activeView = <?= $showCalendar ? "'calendar'" : "'list'" ?>;

    function setView(view, shouldFocus) {
        activeView = view;

        viewBtns.forEach(function(btn) {
            var isActive = btn.getAttribute('data-view') === view;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        var showCalendarView = view === 'calendar';
        listView.hidden = showCalendarView;
        calendarView.hidden = !showCalendarView;

        if (calendarNav) {
            calendarNav.hidden = !showCalendarView;
        }

        if (listNav) {
            listNav.hidden = showCalendarView;
        }

        if (view === 'list') {
            var url = new URL(window.location);
            url.searchParams.delete('week_start');
            window.history.replaceState({}, '', url);
        }

        if (shouldFocus) {
            (showCalendarView ? calendarView : listView).focus();
        }
    }

    viewBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            setView(this.getAttribute('data-view'), true);
        });
    });

    setView(activeView, false);

    var wcScroll = document.getElementById('wcScroll');
    if (wcScroll) {
        wcScroll.scrollTop = 0;
    }
});
</script>
