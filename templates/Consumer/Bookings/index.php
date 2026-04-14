<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var array $calendarEvents
 * @var \Cake\I18n\DateTime $weekStart
 * @var \Cake\I18n\DateTime $weekEnd
 * @var bool $isAdult
 * @var string $userRole
 */
$this->assign('title', 'My Schedule');

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
$bookingList = is_object($bookings) && method_exists($bookings, 'toList') ? $bookings->toList() : (is_array($bookings) ? $bookings : []);
?>

<!-- Toolbar -->
<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
        <!-- Left: Date Nav -->
        <div id="calendarNav" class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $prevWeek]]) ?>" class="admin-action-link view"><i class="bi bi-chevron-left"></i></a>
                <?php if (!$isCurrentWeek): ?>
                    <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $todayWeek]]) ?>" class="admin-tab" style="padding: 4px 12px; font-size: 13px;">Today</a>
                <?php endif; ?>
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $nextWeek]]) ?>" class="admin-action-link view"><i class="bi bi-chevron-right"></i></a>
            </div>
            <h2 class="admin-form-title m-0" style="font-size: 16px;"><?= h($weekStart->format('M j')) ?> — <?= h($weekEnd->format('M j, Y')) ?></h2>
        </div>

        <!-- Right: View Toggle & Book Class -->
        <div class="d-flex align-items-center gap-3">
            <div class="admin-tabs">
                <a href="#" class="admin-tab active sp-view-btn" data-view="calendar"><i class="bi bi-calendar-week"></i> Calendar</a>
                <a href="#" class="admin-tab sp-view-btn" data-view="list"><i class="bi bi-list-ul"></i> List</a>
            </div>
            <?php if ($isAdult): ?>
                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary" style="padding: 8px 16px; font-size: 13px;"><i class="bi bi-plus-circle me-1"></i> Book Class</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ CALENDAR VIEW ============ -->
    <div class="sp-view sp-view--calendar" id="calendarView">
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

            <div class="wc-scroll" id="wcScroll">
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
                                if ($ev['day_index'] !== $weekDays[$d]['dow']) continue;
                                $topMin = ($ev['start_hour'] - $calHourStart) * 60 + $ev['start_minute'];
                                $durMin = ($ev['end_hour'] - $ev['start_hour']) * 60 + ($ev['end_minute'] - $ev['start_minute']);
                                if ($durMin < 30) $durMin = 30;
                                $startFmt = sprintf('%d:%02d', $ev['start_hour'], $ev['start_minute']);
                                $endFmt = sprintf('%d:%02d', $ev['end_hour'], $ev['end_minute']);
                            ?>
                                <a class="wc-evt"
                                   style="top: calc(<?= $topMin ?> * var(--wc-min-h)); height: calc(<?= $durMin ?> * var(--wc-min-h)); --evt-color: <?= h($ev['color']) ?>;"
                                   href="#booking-<?= $ev['booking_id'] ?>"
                                   title="<?= h($ev['title']) ?>">
                                    <strong class="wc-evt__title"><?= h($ev['title']) ?></strong>
                                    <span class="wc-evt__time"><?= $startFmt ?> – <?= $endFmt ?></span>
                                    <span class="wc-evt__loc"><?= h($ev['student_name'] ?? '') ?><?= !empty($ev['student_name']) && $ev['location'] ? ' · ' : '' ?><?= h($ev['location'] ?? '') ?></span>
                                </a>
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

        <?php if (empty($calendarEvents)): ?>
            <div class="text-center py-4 text-muted"><p class="mb-0">No classes scheduled this week.</p></div>
        <?php endif; ?>
    </div>

    <!-- ============ LIST VIEW ============ -->
    <div class="sp-view sp-view--list d-none" id="listView">
        <?php if (empty($bookingList)): ?>
            <div class="admin-form-card text-center py-5 flex-grow-1 d-flex flex-column justify-content-center" style="max-width: 100%;">
                <i class="bi bi-calendar-event text-muted" style="font-size: 48px;"></i>
                <p class="mt-3 text-muted">You have no bookings yet.</p>
                <?php if ($isAdult): ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary btn-sm mt-2 mx-auto">Browse Courses</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
            $grouped = [];
            foreach ($bookingList as $booking) {
                $key = $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('Y-m-d') : '0000-00-00';
                $grouped[$key][] = $booking;
            }
            ksort($grouped);
            ?>
            <?php foreach ($grouped as $dateKey => $dateBookings): ?>
                <div class="sp-list-date">
                    <div class="sp-list-date__label">
                        <?php if ($dateKey === $todayStr): ?><span class="sp-list-date__badge">Today</span><?php endif; ?>
                        <?= $dateKey !== '0000-00-00' ? date('l, M j, Y', strtotime($dateKey)) : 'Unscheduled' ?>
                    </div>
                    <?php foreach ($dateBookings as $booking): ?>
                        <div class="sp-list-card" id="booking-<?= $booking->booking_id ?>" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; transition: border-color 0.2s;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 40px; height: 40px; border-radius: 8px; background-color: var(--admin-search-bg); display: flex; justify-content: center; align-items: center;">
                                    <i class="bi bi-calendar-event" style="font-size: 18px; color: var(--admin-text-secondary);"></i>
                                </div>
                                <div>
                                    <?php if ($userRole === 'parent'): ?>
                                        <div class="mt-1" style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary); margin-bottom: 2px;">
                                            <?= h($booking->student?->student_name ?? '-') ?>
                                        </div>
                                    <?php endif; ?>
                                    <h4 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin: 0 0 2px 0;">
                                        <?= h($booking->class_entity?->course?->course_name ?? '-') ?>
                                    </h4>
                                    <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); display: flex; align-items: center; gap: 8px;">
                                        <span><?= h($booking->class_entity?->class_code ?? '-') ?></span>
                                        <span style="opacity: 0.5;">•</span>
                                        <span><?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('g:ia') : '-' ?> - <?= $booking->class_entity?->end_datetime ? $booking->class_entity->end_datetime->format('g:ia') : '-' ?></span>
                                        <?php if ($booking->class_entity?->location): ?>
                                            <span style="opacity: 0.5;">•</span>
                                            <span><?= h($booking->class_entity->location) ?></span>
                                        <?php endif; ?>
                                        <?php if ($booking->class_entity?->teacher): ?>
                                            <span style="opacity: 0.5;">•</span>
                                            <span><?= h($booking->class_entity->teacher->teacher_name) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <?php 
                                    $statusClass = 'admin-badge-neutral';
                                    if ($booking->booking_status === 'Confirmed') $statusClass = 'admin-badge-success';
                                    if ($booking->booking_status === 'Pending') $statusClass = 'admin-badge-warning';
                                    if ($booking->booking_status === 'Cancelled') $statusClass = 'admin-badge-danger';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($booking->booking_status)) ?></span>
                                <?php
                                $hasPaid = false;
                                foreach ($booking->payments ?? [] as $p) { if ($p->payment_status === 'paid') { $hasPaid = true; break; } }
                                $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaid;
                                ?>
                                <?php if ($isAdult): ?>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?php if ($booking->booking_status === 'pending' && !$isPaid): ?>
                                            <?php if ($ageVerifiedByAdmin): ?>
                                                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'process', $booking->booking_id]) ?>" class="admin-btn-primary" style="padding: 6px 12px; font-size: 12px;">Pay</a>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-warning" title="Admin must verify your age before payment"><i class="bi bi-hourglass-split me-1"></i>Awaiting Verification</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                            <?= $this->Form->postLink('Cancel', ['action' => 'cancel', $booking->booking_id], ['class' => 'admin-action-link delete', 'style' => 'padding: 6px 12px; height: auto; width: auto; font-size: 12px;', 'confirm' => 'Cancel this booking?']) ?>
                                        <?php endif; ?>
                                        <?php if ($isPaid): ?>
                                            <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'receipt', collection($booking->payments)->last()->payment_id]) ?>" class="admin-action-link view" style="padding: 6px 12px; height: auto; width: auto; font-size: 12px;">Receipt</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View Toggle
    var viewBtns = document.querySelectorAll('.sp-view-btn');
    var listView = document.getElementById('listView');
    var calendarView = document.getElementById('calendarView');
    var calendarNav = document.getElementById('calendarNav');

    viewBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var view = this.getAttribute('data-view');
            
            // Update buttons
            viewBtns.forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.sp-view-btn[data-view="' + view + '"]').forEach(function(b) { b.classList.add('active'); });
            
            // Update views
            if (view === 'list') {
                listView.classList.remove('d-none');
                calendarView.classList.add('d-none');
                if (calendarNav) calendarNav.classList.add('d-none');
                // Remove week_start from URL to make list view default on refresh
                var url = new URL(window.location);
                url.searchParams.delete('week_start');
                window.history.replaceState({}, '', url);
            } else {
                listView.classList.add('d-none');
                calendarView.classList.remove('d-none');
                if (calendarNav) calendarNav.classList.remove('d-none');
            }
        });
    });

    // Scroll calendar to 8am
    var wcScroll = document.getElementById('wcScroll');
    if (wcScroll) {
        wcScroll.scrollTop = 0;
    }
});
</script>
