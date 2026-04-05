<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 * @var array $children
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

<div class="schedule-page">
    <!-- Toolbar -->
    <div class="sp-toolbar">
        <div class="sp-toolbar__left">
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-secondary sp-view-btn active" data-view="calendar"><i class="bi bi-calendar-week"></i> Calendar</button>
                <button class="btn btn-sm btn-outline-secondary sp-view-btn" data-view="list"><i class="bi bi-list-ul"></i> List</button>
            </div>
        </div>
        <div class="sp-toolbar__center">
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $prevWeek]]) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <?php if (!$isCurrentWeek): ?>
                <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm btn-outline-primary">Today</a>
            <?php endif; ?>
            <a href="<?= $this->Url->build(['action' => 'index', '?' => ['week_start' => $nextWeek]]) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
            <span class="sp-toolbar__title"><?= h($weekStart->format('M j')) ?> — <?= h($weekEnd->format('M j, Y')) ?></span>
        </div>
        <div class="sp-toolbar__right">
            <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle me-1"></i> Book Class</a>
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
        </div>

        <?php if (empty($calendarEvents)): ?>
            <div class="text-center py-4 text-muted"><p class="mb-0">No classes scheduled this week.</p></div>
        <?php endif; ?>
    </div>

    <!-- ============ LIST VIEW ============ -->
    <div class="sp-view sp-view--list d-none" id="listView">
        <?php if (empty($bookingList)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-event" style="font-size: 48px;"></i>
                <p class="mt-3">No bookings yet.</p>
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
                        <div class="sp-list-card" id="booking-<?= $booking->booking_id ?>">
                            <div class="sp-list-card__time">
                                <?php if ($booking->class_entity?->start_datetime): ?>
                                    <strong><?= $booking->class_entity->start_datetime->format('g:ia') ?></strong>
                                    <span><?= $booking->class_entity?->end_datetime ? $booking->class_entity->end_datetime->format('g:ia') : '' ?></span>
                                <?php else: ?>
                                    <strong>TBA</strong>
                                <?php endif; ?>
                            </div>
                            <div class="sp-list-card__body">
                                <h6 class="mb-1"><?= h($booking->class_entity?->course?->course_name ?? 'Class') ?></h6>
                                <div class="sp-list-card__meta">
                                    <span><i class="bi bi-person-fill"></i> <?= h($booking->student?->student_name ?? '-') ?></span>
                                    <span><i class="bi bi-geo-alt"></i> <?= h($booking->class_entity?->location ?? '-') ?></span>
                                    <span><i class="bi bi-tag"></i> <?= h($booking->class_entity?->class_code ?? '-') ?></span>
                                </div>
                            </div>
                            <div class="sp-list-card__actions">
                                <span class="badge badge-<?= h($booking->booking_status) ?>"><?= ucfirst(h($booking->booking_status)) ?></span>
                                <?php
                                $hasPaid = false;
                                foreach ($booking->payments ?? [] as $p) { if ($p->payment_status === 'paid') { $hasPaid = true; break; } }
                                $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaid;
                                ?>
                                <div class="d-flex gap-1 mt-2 flex-wrap">
                                    <?php if ($booking->booking_status === 'pending' && !$isPaid): ?>
                                        <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'process', $booking->booking_id]) ?>" class="btn btn-sm btn-primary">Pay</a>
                                    <?php endif; ?>
                                    <?php if (in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                        <?= $this->Form->postLink('Cancel', ['action' => 'cancel', $booking->booking_id], ['class' => 'btn btn-sm btn-outline-danger', 'confirm' => 'Cancel this booking?']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
