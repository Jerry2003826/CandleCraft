<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Booking> $bookings
 */
$this->assign('title', 'My Schedule');
$todayStr = date('Y-m-d');
?>

<div class="schedule-page">
    <div class="sp-toolbar">
        <div class="sp-toolbar__left">
            <h1 class="page-title">My Schedule</h1>
        </div>
        <div class="sp-toolbar__right">
            <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Book a Class
            </a>
        </div>
    </div>

    <div class="sp-view sp-view--list">
        <?php if ($bookings->isEmpty()): ?>
            <div class="text-center py-5 text-muted card shadow-sm">
                <i class="bi bi-calendar-x" style="font-size: 48px;"></i>
                <p class="mt-3">You don't have any bookings scheduled yet.</p>
            </div>
        <?php else: ?>
            <?php
            // Group by date
            $grouped = [];
            foreach ($bookings as $booking) {
                $dateKey = $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('Y-m-d') : 'Unscheduled';
                $grouped[$dateKey][] = $booking;
            }
            ?>
            
            <?php foreach ($grouped as $dateKey => $dateBookings): ?>
                <div class="sp-list-date mb-4">
                    <div class="sp-list-date__label mb-2">
                        <strong><?= $dateKey !== 'Unscheduled' ? date('l, j M Y', strtotime($dateKey)) : 'Date TBA' ?></strong>
                        <?php if ($dateKey === $todayStr): ?><span class="badge bg-primary ms-2">Today</span><?php endif; ?>
                    </div>

                    <?php foreach ($dateBookings as $booking): ?>
                        <div class="sp-list-card card mb-2 p-3 shadow-sm flex-row align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-4">
                                <div class="text-center" style="min-width: 80px;">
                                    <h5 class="mb-0"><?= $booking->class_entity?->start_datetime?->format('g:ia') ?? 'TBA' ?></h5>
                                    <small class="text-muted"><?= $booking->class_entity?->end_datetime?->format('g:ia') ?? '' ?></small>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?= h($booking->class_entity?->course?->course_name ?? 'Class') ?></h6>
                                    <div class="small text-muted">
                                        <span class="me-2"><i class="bi bi-geo-alt"></i> <?= h($booking->class_entity?->location ?? 'TBA') ?></span>
                                        <span><i class="bi bi-person"></i> <?= h($booking->class_entity?->teacher?->teacher_name ?? 'TBA') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge rounded-pill bg-<?= $booking->booking_status === 'confirmed' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst(h($booking->booking_status)) ?>
                                </span>
                                <div class="mt-2">
                                    <?php if ($booking->booking_status !== 'cancelled'): ?>
                                        <?= $this->Form->postLink('Cancel', ['action' => 'cancel', $booking->booking_id], [
                                            'confirm' => 'Are you sure you want to cancel?',
                                            'class' => 'btn btn-sm btn-outline-danger'
                                        ]) ?>
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

<style>
    /* Remove any leftover calendar CSS effects */
    .sp-view--calendar { display: none !important; }
    .sp-view--list { display: block !important; }
    .sp-list-card { border-left: 4px solid #bfa487; } /* A nice accent for the list cards */
</style>