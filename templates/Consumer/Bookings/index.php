<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var array $calendarEvents
 * @var \Cake\I18n\DateTime $weekStart
 * @var \Cake\I18n\DateTime $weekEnd
 */
$this->assign('title', 'My Schedule');

$prevWeek = $weekStart->modify('-7 days')->format('Y-m-d');
$nextWeek = $weekStart->modify('+7 days')->format('Y-m-d');
$todayWeek = (new \Cake\I18n\DateTime('now'))->modify('-' . date('w') . ' days')->format('Y-m-d');
$todayStr = date('Y-m-d');
$nowHour = (int)date('G');
$nowMinute = (int)date('i');
$isCurrentWeek = $weekStart->format('Y-m-d') === $todayWeek;
$showCalendar = $this->request->getQuery('week_start') !== null;

$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = $weekStart->modify("+{$i} days");
    $weekDays[$i] = [
        'name' => $dayNames[(int)$d->format('w')],
        'date' => (int)$d->format('j'),
        'full' => $d->format('Y-m-d'),
    ];
}

$calHourStart = 8;
$calHourEnd = 22;

$bookingList = is_object($bookings) && method_exists($bookings, 'toList')
    ? $bookings->toList()
    : (is_array($bookings) ? $bookings : []);

$resolvePaymentBadge = static function ($booking): array {
    $hasPaid = false;
    foreach ($booking->payments ?? [] as $payment) {
        if ((string)$payment->payment_status === 'paid') $hasPaid = true;
    }
    if (in_array((string)$booking->booking_status, ['confirmed', 'completed'], true) && $hasPaid) {
        return ['class' => 'admin-badge-success', 'label' => 'Payment Paid'];
    }
    return ['class' => 'admin-badge-warning', 'label' => 'Payment Pending'];
};

$bookingStatusBadge = static fn(string $s): string => match ($s) {
    'confirmed' => 'admin-badge-success',
    'pending'   => 'admin-badge-warning',
    'completed' => 'admin-badge-neutral',
    default     => 'admin-badge-danger',
};

$upcomingBookings = [];
$pastBookings = [];
foreach ($bookingList as $booking) {
    if (in_array($booking->booking_status, ['completed', 'cancelled'], true)) {
        $pastBookings[] = $booking;
    } else {
        $upcomingBookings[] = $booking;
    }
}

$groupByDate = static function (array $list): array {
    $grouped = [];
    foreach ($list as $booking) {
        $key = $booking->class_entity?->start_datetime
            ? $booking->class_entity->start_datetime->format('Y-m-d')
            : '0000-00-00';
        $grouped[$key][] = $booking;
    }
    ksort($grouped);
    return $grouped;
};
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
    <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Book New Class
    </a>

    <div class="d-flex align-items-center gap-3">
        <div id="calendarNav" style="display:<?= $showCalendar ? 'flex' : 'none' ?>; align-items:center; gap:8px;">
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $prevWeek]]) ?>" class="admin-action-link view" aria-label="Show previous week"><i class="bi bi-chevron-left"></i></a>
            <?php if (!$isCurrentWeek): ?>
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $todayWeek]]) ?>" class="admin-pill-tab" style="text-decoration:none;">Today</a>
            <?php endif; ?>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $nextWeek]]) ?>" class="admin-action-link view" aria-label="Show next week"><i class="bi bi-chevron-right"></i></a>
            <span style="font-size:13px; color:var(--admin-text-secondary); font-weight:500; white-space:nowrap;">
                <?= h($weekStart->format('j M')) ?> - <?= h($weekEnd->format('j M Y')) ?>
            </span>
        </div>

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

    <?php if (empty($bookingList)): ?>
        <div class="admin-form-card text-center py-5" style="max-width:100%;">
            <i class="bi bi-calendar-event" style="font-size:48px; color:var(--admin-text-secondary);"></i>
            <p class="mt-3" style="color:var(--admin-text-secondary);">You have no bookings yet.</p>
            <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary mt-3 mx-auto">Browse Classes</a>
        </div>
    <?php else: ?>

        <div class="admin-tabs mb-4" role="tablist">
            <button type="button" class="admin-tab active" id="tab-upcoming" role="tab" aria-controls="panel-upcoming" aria-selected="true">
                Upcoming
                <?php if (!empty($upcomingBookings)): ?><span class="admin-badge admin-badge-neutral" style="font-size:10px; margin-left:4px;"><?= count($upcomingBookings) ?></span><?php endif; ?>
            </button>
            <button type="button" class="admin-tab" id="tab-past" role="tab" aria-controls="panel-past" aria-selected="false">
                Completed
                <?php if (!empty($pastBookings)): ?><span class="admin-badge admin-badge-neutral" style="font-size:10px; margin-left:4px;"><?= count($pastBookings) ?></span><?php endif; ?>
            </button>
        </div>

        <?php
        // Reusable booking card renderer
        $renderBookingGroups = function (array $list, bool $showActions) use ($groupByDate, $todayStr, $resolvePaymentBadge, $bookingStatusBadge): void {
            foreach ($groupByDate($list) as $dateKey => $dateBookings):
        ?>
            <div>
                <div class="d-flex align-items-center gap-2 mb-3" style="padding-bottom:10px; border-bottom:1px solid var(--admin-card-border);">
                    <span style="font-weight:700; font-size:14px; color:var(--admin-text-primary);">
                        <?= $dateKey !== '0000-00-00' ? date('l, j M Y', strtotime($dateKey)) : 'Unscheduled' ?>
                    </span>
                    <?php if ($dateKey === $todayStr): ?>
                        <span class="admin-badge admin-badge-info" style="font-size:10px;">Today</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($dateBookings as $booking):
                        $paymentBadge = $resolvePaymentBadge($booking);
                        $statusClass  = $bookingStatusBadge((string)$booking->booking_status);
                        $canCancel    = $showActions && in_array($booking->booking_status, ['pending', 'confirmed'], true);
                    ?>
                        <div style="background-color:var(--admin-card-bg); border:1px solid var(--admin-card-border); border-left:4px solid var(--admin-gold); border-radius:12px; padding:20px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                        <span style="font-weight:600; font-size:15px; color:var(--admin-text-primary);">
                                            <?= h($booking->class_entity?->course?->course_name ?? '-') ?>
                                        </span>
                                        <span style="font-size:12px; color:var(--admin-gold); font-weight:600;">
                                            <?= h($booking->class_entity?->class_code ?? '') ?>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3" style="font-size:13px; color:var(--admin-text-secondary);">
                                        <?php if ($booking->class_entity?->start_datetime): ?>
                                            <span>
                                                <i class="bi bi-clock me-1"></i>
                                                <?= $booking->class_entity->start_datetime->format('g:ia') ?>
                                                <?= $booking->class_entity?->end_datetime ? ' - ' . $booking->class_entity->end_datetime->format('g:ia') : '' ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($booking->class_entity?->location): ?>
                                            <span><i class="bi bi-geo-alt me-1"></i><?= h($booking->class_entity->location) ?></span>
                                        <?php endif; ?>
                                        <?php if ($booking->class_entity?->teacher?->teacher_name): ?>
                                            <span><i class="bi bi-person me-1"></i><?= h($booking->class_entity->teacher->teacher_name) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                                        <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($booking->booking_status)) ?></span>
                                        <span class="admin-badge <?= h($paymentBadge['class']) ?>"><?= h($paymentBadge['label']) ?></span>
                                    </div>
                                    <?php if ($showActions): ?>
                                        <div class="d-flex gap-2">
                                            <?php if ($booking->booking_status === 'pending'): ?>
                                                <a href="<?= $this->Url->build(['controller' => 'Payments', 'action' => 'process', $booking->booking_id]) ?>" class="admin-btn-primary" style="padding:5px 14px; font-size:12px;">
                                                    <i class="bi bi-credit-card me-1"></i>Pay
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($canCancel): ?>
                                                <?= $this->Form->create(null, ['url' => ['action' => 'cancel', $booking->booking_id], 'class' => 'd-inline m-0']) ?>
                                                    <?= $this->Form->button('Cancel', [
                                                        'class' => 'admin-btn-secondary',
                                                        'style' => 'padding:5px 14px; font-size:12px;',
                                                        'onclick' => "return confirm('Are you sure you want to cancel this booking?');",
                                                    ]) ?>
                                                <?= $this->Form->end() ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php
            endforeach;
        };
        ?>

        <!-- Upcoming panel -->
        <div id="panel-upcoming" role="tabpanel">
            <?php if (empty($upcomingBookings)): ?>
                <div class="admin-form-card text-center py-5" style="max-width:100%;">
                    <i class="bi bi-calendar-check" style="font-size:48px; color:var(--admin-text-secondary);"></i>
                    <p class="mt-3" style="color:var(--admin-text-secondary);">No upcoming bookings.</p>
                    <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary mt-3 mx-auto">Browse Classes</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-4">
                    <?php $renderBookingGroups($upcomingBookings, true); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed panel -->
        <div id="panel-past" role="tabpanel" hidden>
            <?php if (empty($pastBookings)): ?>
                <div class="admin-form-card text-center py-5" style="max-width:100%;">
                    <i class="bi bi-archive" style="font-size:48px; color:var(--admin-text-secondary);"></i>
                    <p class="mt-3" style="color:var(--admin-text-secondary);">No completed classes yet.</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-4">
                    <?php $renderBookingGroups($pastBookings, false); ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<!-- ============ CALENDAR VIEW ============ -->
<div id="calendarView" tabindex="-1" <?= !$showCalendar ? 'hidden' : '' ?>>
    <div class="wc-wrapper">
        <div class="wc-header">
            <div class="wc-gutter-header"></div>
            <?php foreach ($weekDays as $wd): ?>
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
                        $endFmt   = sprintf('%d:%02d', $ev['end_hour'], $ev['end_minute']);
                    ?>
                        <div class="wc-evt" style="top:calc(<?= $topMin ?> * var(--wc-min-h)); height:calc(<?= $durMin ?> * var(--wc-min-h)); --evt-color:<?= h($ev['color']) ?>;">
                            <strong class="wc-evt__title"><?= h($ev['title']) ?></strong>
                            <span class="wc-evt__time"><?= $startFmt ?> - <?= $endFmt ?></span>
                            <?php if ($ev['location']): ?>
                                <span class="wc-evt__loc"><?= h($ev['location']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($isCurrentWeek && $weekDays[$d]['full'] === $todayStr && $nowHour >= $calHourStart && $nowHour < $calHourEnd): ?>
                        <div class="wc-now-line" style="top:calc(<?= ($nowHour - $calHourStart) * 60 + $nowMinute ?> * var(--wc-min-h));">
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
    var listView = document.getElementById('listView');
    var calendarView = document.getElementById('calendarView');
    var calendarNav = document.getElementById('calendarNav');
    var activeView = <?= $showCalendar ? "'calendar'" : "'list'" ?>;

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

    // --- List tabs (Upcoming / Completed) ---
    var listTabs = document.querySelectorAll('#tab-upcoming, #tab-past');
    listTabs.forEach(function (tab) {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            var panelId = this.getAttribute('aria-controls');
            listTabs.forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
                document.getElementById(t.getAttribute('aria-controls')).hidden = true;
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            document.getElementById(panelId).hidden = false;
        });
    });

    setView(activeView, false);
});
</script>
