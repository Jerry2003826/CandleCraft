<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var array $calendarEvents
 * @var \Cake\I18n\DateTime $weekStart
 * @var \Cake\I18n\DateTime $weekEnd
 * @var bool $bookingAccessEnabled
 */
$this->assign('title', 'View Schedule & Attendance');

$prevWeek = $weekStart->modify('-7 days')->format('Y-m-d');
$nextWeek = $weekStart->modify('+7 days')->format('Y-m-d');
$todayWeek = (new \Cake\I18n\DateTime('now'))->modify('-' . date('w') . ' days')->format('Y-m-d');
$todayStr = date('Y-m-d');
$nowHour = (int)date('G');
$nowMinute = (int)date('i');
$now = new \Cake\I18n\DateTime('now');
$reminderWindowStartTs = $now->modify('+24 hours')->getTimestamp();
$reminderWindowEndTs = $now->modify('+25 hours')->getTimestamp();

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
$resolvePaymentBadge = static function ($booking): array {
    $latestPaymentStatus = null;
    $hasPaid = false;

    foreach ($booking->payments ?? [] as $payment) {
        $latestPaymentStatus = (string)$payment->payment_status;
        if ($latestPaymentStatus === 'paid') {
            $hasPaid = true;
        }
    }

    if (in_array((string)$booking->booking_status, ['confirmed', 'completed'], true) && $hasPaid) {
        return ['class' => 'admin-badge-success', 'label' => 'Payment Paid'];
    }

    return match ($latestPaymentStatus) {
        'failed' => ['class' => 'admin-badge-danger', 'label' => 'Payment Failed'],
        'expired', 'voided' => ['class' => 'admin-badge-neutral', 'label' => 'Payment Cancelled'],
        'refund_required' => ['class' => 'admin-badge-warning', 'label' => 'Refund Required'],
        'refunded' => ['class' => 'admin-badge-neutral', 'label' => 'Refunded'],
        'partially_refunded' => ['class' => 'admin-badge-info', 'label' => 'Partially Refunded'],
        default => ['class' => 'admin-badge-warning', 'label' => 'Payment Pending'],
    };
};
?>

<!-- Toolbar -->
<div class="admin-page-header d-flex justify-content-between align-items-center mb-4" data-view-toggle-managed="custom">
        <!-- Left: Date Nav -->
        <div id="calendarNav" class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $prevWeek]]) ?>" class="admin-action-link view" aria-label="Show previous week"><i class="bi bi-chevron-left"></i></a>
                <?php if (!$isCurrentWeek): ?>
                    <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $todayWeek]]) ?>" class="admin-tab" style="padding: 4px 12px; font-size: 13px;">Today</a>
                <?php endif; ?>
                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $nextWeek]]) ?>" class="admin-action-link view" aria-label="Show next week"><i class="bi bi-chevron-right"></i></a>
            </div>
            <h2 class="admin-form-title m-0" style="font-size: 16px;"><?= h($weekStart->format('M j')) ?> — <?= h($weekEnd->format('M j, Y')) ?></h2>
        </div>

        <!-- Right: View Toggle & Book Class -->
        <div class="d-flex align-items-center gap-3">
            <div class="admin-tabs">
                <button type="button" class="admin-tab active sp-view-btn" data-view="calendar" aria-pressed="true" aria-controls="calendarView">
                    <i class="bi bi-calendar-week"></i> Calendar
                </button>
                <button type="button" class="admin-tab sp-view-btn" data-view="list" aria-pressed="false" aria-controls="listView">
                    <i class="bi bi-list-ul"></i> List
                </button>
            </div>
            <?php if ($bookingAccessEnabled): ?>
                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary" style="padding: 8px 16px; font-size: 13px;"><i class="bi bi-plus-circle me-1"></i> Open Booking System</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ CALENDAR VIEW ============ -->
    <div class="sp-view sp-view--calendar" id="calendarView" tabindex="-1">
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
                                $eventLabelParts = [
                                    $ev['title'],
                                    $startFmt . ' to ' . $endFmt,
                                    $ev['location'] ?? null,
                                ];
                                if (!empty($ev['attendance_status'])) {
                                    $eventLabelParts[] = 'Attendance ' . ucfirst((string)$ev['attendance_status']);
                                } elseif (!empty($ev['reminder_sent_at'])) {
                                    $eventLabelParts[] = 'Reminder sent';
                                }
                            ?>
                                <a class="wc-evt"
                                   style="top: calc(<?= $topMin ?> * var(--wc-min-h)); height: calc(<?= $durMin ?> * var(--wc-min-h)); --evt-color: <?= h($ev['color']) ?>;"
                                   href="#booking-<?= $ev['booking_id'] ?>"
                                   aria-label="<?= h(implode('. ', array_filter($eventLabelParts))) ?>">
                                    <strong class="wc-evt__title"><?= h($ev['title']) ?></strong>
                                    <span class="wc-evt__time"><?= $startFmt ?> – <?= $endFmt ?></span>
                                    <span class="wc-evt__loc"><?= h($ev['location'] ?? '') ?></span>
                                    <?php if (!empty($ev['attendance_status'])): ?>
                                        <span class="wc-evt__loc">Attendance: <?= h(ucfirst((string)$ev['attendance_status'])) ?></span>
                                    <?php elseif (!empty($ev['reminder_sent_at'])): ?>
                                        <span class="wc-evt__loc">Reminder sent</span>
                                    <?php endif; ?>
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
    <div class="sp-view sp-view--list" id="listView" tabindex="-1" hidden>
        <?php if (empty($bookingList)): ?>
            <div class="admin-form-card text-center py-5 flex-grow-1 d-flex flex-column justify-content-center" style="max-width: 100%;">
                <i class="bi bi-calendar-event text-muted" style="font-size: 48px;"></i>
                <p class="mt-3 text-muted">You do not have any schedule or attendance records yet.</p>
                <?php if ($bookingAccessEnabled): ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary btn-sm mt-2 mx-auto">Open Booking System</a>
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
                        <?php
                            $classStart = $booking->class_entity?->start_datetime;
                            $attendanceStatus = (string)($booking->attendance_record?->attendance_status ?? '');
                            $attendanceLabel = 'Upcoming';
                            $attendanceClass = 'admin-badge-neutral';
                            if ($attendanceStatus !== '') {
                                $attendanceLabel = ucfirst($attendanceStatus);
                                $attendanceClass = match ($attendanceStatus) {
                                    'present' => 'admin-badge-success',
                                    'late', 'excused' => 'admin-badge-warning',
                                    'absent' => 'admin-badge-danger',
                                    default => 'admin-badge-neutral',
                                };
                            } elseif ($classStart && $classStart->isPast()) {
                                $attendanceLabel = 'Awaiting mark';
                                $attendanceClass = 'admin-badge-warning';
                            }

                            $reminderLabel = null;
                            $reminderClass = 'admin-badge-info';
                            if ($booking->reminder_sent_at) {
                                $reminderLabel = 'Reminder sent ' . $booking->reminder_sent_at->format('j M, g:ia');
                                $reminderClass = 'admin-badge-success';
                            } elseif (
                                $classStart
                                && in_array($booking->booking_status, ['confirmed', 'completed'], true)
                                && $classStart->getTimestamp() >= $reminderWindowStartTs
                                && $classStart->getTimestamp() < $reminderWindowEndTs
                            ) {
                                $reminderLabel = 'Reminder due soon';
                            }
                        ?>
                        <div class="sp-list-card" id="booking-<?= $booking->booking_id ?>" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; transition: border-color 0.2s;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 40px; height: 40px; border-radius: 8px; background-color: var(--admin-search-bg); display: flex; justify-content: center; align-items: center;">
                                    <i class="bi bi-calendar-event" style="font-size: 18px; color: var(--admin-text-secondary);"></i>
                                </div>
                                <div>
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
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <?php 
                                            $statusClass = 'admin-badge-neutral';
                                            if ($booking->booking_status === 'confirmed') $statusClass = 'admin-badge-success';
                                            if ($booking->booking_status === 'pending') $statusClass = 'admin-badge-warning';
                                            if ($booking->booking_status === 'cancelled') $statusClass = 'admin-badge-danger';
                                        ?>
                                        <span class="admin-badge <?= $statusClass ?>">Booking: <?= ucfirst(h($booking->booking_status)) ?></span>
                                        <span class="admin-badge <?= $attendanceClass ?>">Attendance: <?= h($attendanceLabel) ?></span>
                                        <?php if ($reminderLabel): ?>
                                            <span class="admin-badge <?= $reminderClass ?>"><?= h($reminderLabel) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <?php
                                $hasPaid = false;
                                foreach ($booking->payments ?? [] as $p) { if ($p->payment_status === 'paid') { $hasPaid = true; break; } }
                                $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaid;
                                $paymentBadge = $resolvePaymentBadge($booking);
                                ?>
                                <?php if ($bookingAccessEnabled): ?>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="admin-badge <?= h($paymentBadge['class']) ?>"><?= h($paymentBadge['label']) ?></span>
                                        <?php if ($booking->booking_status === 'pending' && !$isPaid): ?>
                                            <?php if ($ageVerifiedByAdmin): ?>
                                                <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'process', $booking->booking_id]) ?>" class="admin-btn-primary" style="padding: 6px 12px; font-size: 12px;">Pay</a>
                                            <?php else: ?>
                                                <span class="admin-badge admin-badge-warning" title="Admin must verify your age before payment"><i class="bi bi-hourglass-split me-1"></i>Awaiting Verification</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                            <?= $this->Form->create(null, [
                                                'url' => ['action' => 'cancel', $booking->booking_id],
                                                'class' => 'd-inline m-0',
                                            ]) ?>
                                                <?= $this->Form->button('Cancel', [
                                                    'class' => 'admin-action-link delete',
                                                    'style' => 'padding: 6px 12px; height: auto; width: auto; font-size: 12px;',
                                                    'type' => 'submit',
                                                    'onclick' => "return confirm('Cancel this booking?');",
                                                ]) ?>
                                            <?= $this->Form->end() ?>
                                        <?php endif; ?>
                                        <?php if ($isPaid): ?>
                                            <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'receipt', collection($booking->payments)->last()->payment_id]) ?>" class="admin-action-link view" style="padding: 6px 12px; height: auto; width: auto; font-size: 12px;">Receipt</a>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($booking->booking_status === 'pending'): ?>
                                    <span class="admin-badge admin-badge-warning"><i class="bi bi-hourglass-split me-1"></i>Awaiting adult verification</span>
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
    var viewBtns = document.querySelectorAll('.sp-view-btn');
    var listView = document.getElementById('listView');
    var calendarView = document.getElementById('calendarView');
    var calendarNav = document.getElementById('calendarNav');
    var activeView = 'calendar';

    function setView(view, shouldFocus) {
        activeView = view;

        viewBtns.forEach(function(btn) {
            var isActive = btn.getAttribute('data-view') === view;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        var showCalendar = view === 'calendar';
        calendarView.hidden = !showCalendar;
        listView.hidden = showCalendar;

        if (calendarNav) {
            calendarNav.hidden = !showCalendar;
        }

        if (view === 'list') {
            var url = new URL(window.location);
            url.searchParams.delete('week_start');
            window.history.replaceState({}, '', url);
        }

        if (shouldFocus) {
            (showCalendar ? calendarView : listView).focus();
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
