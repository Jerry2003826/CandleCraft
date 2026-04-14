<?php
/**
 * @var \App\View\AppView $this
 * @var int $totalEnquiries
 * @var int $newMessages
 * @var int $repliedMessages
 * @var int $totalStudents
 * @var int $totalTeachers
 * @var int $totalClasses
 * @var int $totalBookings
 * @var int $pendingBookings
 * @var \Cake\ORM\ResultSet $recentMessages
 * @var \Cake\ORM\ResultSet $recentBookings
 */
$this->assign('title', 'Dashboard');
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Active Students</h3>
                <div class="admin-stat-icon"><i class="bi bi-people"></i></div>
            </div>
            <div class="admin-stat-value"><?= $totalStudents ?></div>
            <div class="admin-stat-trend up">↑ 12% this month</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Active Teachers</h3>
                <div class="admin-stat-icon"><i class="bi bi-person-badge"></i></div>
            </div>
            <div class="admin-stat-value"><?= $totalTeachers ?></div>
            <div class="admin-stat-trend up">↑ 2 new this month</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Upcoming Classes</h3>
                <div class="admin-stat-icon"><i class="bi bi-calendar3"></i></div>
            </div>
            <div class="admin-stat-value"><?= $totalClasses ?></div>
            <div class="admin-stat-trend neutral">Next 7 days</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Active Bookings</h3>
                <div class="admin-stat-icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
            <div class="admin-stat-value"><?= $totalBookings ?></div>
            <div class="admin-stat-trend up">↑ 8% this month</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Total Enquiries</h3>
                <div class="admin-stat-icon"><i class="bi bi-chat-square-text"></i></div>
            </div>
            <div class="admin-stat-value"><?= $totalEnquiries ?></div>
            <div class="admin-stat-trend up">↑ +5 new</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">New Enquiries</h3>
                <div class="admin-stat-icon"><i class="bi bi-envelope"></i></div>
            </div>
            <div class="admin-stat-value"><?= $newMessages ?></div>
            <div class="admin-stat-trend neutral">Unread</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Replied</h3>
                <div class="admin-stat-icon"><i class="bi bi-reply"></i></div>
            </div>
            <div class="admin-stat-value"><?= $repliedMessages ?></div>
            <div class="admin-stat-trend neutral">This week</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Awaiting Confirm</h3>
                <div class="admin-stat-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="admin-stat-value"><?= $pendingBookings ?></div>
            <div class="admin-stat-trend down">↓ Pending action</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h3 class="admin-table-title">Recent Bookings</h3>
                <a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'index']) ?>" class="admin-btn-view-all">View All</a>
            </div>
            <table class="admin-table no-headers">
                <thead><tr><th>Student</th><th>Course</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if ($recentBookings->isEmpty()): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No bookings yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $booking): ?>
                        <tr>
                            <td>
                                <p class="admin-table-primary-text"><?= h($booking->student?->student_name ?? '-') ?></p>
                                <p class="admin-table-secondary-text"><?= h($booking->class_entity?->course?->course_name ?? '-') ?></p>
                            </td>
                            <td>
                                <p class="admin-table-primary-text"><?= h($booking->class_entity?->class_code ?? '-') ?></p>
                                <p class="admin-table-secondary-text"><?= $booking->booking_date ? $booking->booking_date->format('j M, g:ia') : '-' ?></p>
                            </td>
                            <td class="text-end">
                                <?php 
                                    $statusClass = 'admin-badge-neutral';
                                    if ($booking->booking_status === 'Confirmed') $statusClass = 'admin-badge-success';
                                    if ($booking->booking_status === 'Pending') $statusClass = 'admin-badge-warning';
                                    if ($booking->booking_status === 'Cancelled') $statusClass = 'admin-badge-danger';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>"><?= h($booking->booking_status) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h3 class="admin-table-title">Recent Enquiries</h3>
                <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'index']) ?>" class="admin-btn-view-all">View All</a>
            </div>
            <table class="admin-table no-headers">
                <thead><tr><th>From</th><th>Enquiry</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if ($recentMessages->isEmpty()): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No messages yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMessages as $message): ?>
                        <tr>
                            <td>
                                <p class="admin-table-primary-text">
                                    <?php if ($message->sender_user): ?>
                                        <?= h($message->sender_user->username) ?>
                                    <?php else: ?>
                                        <?= h($message->sender_name ?: 'Unknown') ?>
                                    <?php endif; ?>
                                </p>
                                <p class="admin-table-secondary-text"><?= h(\Cake\Utility\Text::truncate($message->subject, 40)) ?></p>
                            </td>
                            <td>
                                <p class="admin-table-primary-text"><?= h($message->source_page ?: '-') ?></p>
                                <p class="admin-table-secondary-text"><?= $message->sent_at ? $message->sent_at->format('j M') : '-' ?></p>
                            </td>
                            <td class="text-end">
                                <?php 
                                    $statusClass = 'admin-badge-neutral';
                                    if ($message->message_status === 'New') $statusClass = 'admin-badge-info';
                                    if ($message->message_status === 'Replied') $statusClass = 'admin-badge-neutral';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>"><?= h($message->message_status) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
