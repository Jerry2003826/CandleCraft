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
        <div class="card stat-card stat-primary text-center p-3">
            <div class="stat-label">Active Students</div>
            <div class="stat-value"><?= $totalStudents ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-success text-center p-3">
            <div class="stat-label">Active Teachers</div>
            <div class="stat-value"><?= $totalTeachers ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-info text-center p-3">
            <div class="stat-label">Upcoming Classes</div>
            <div class="stat-value"><?= $totalClasses ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-warning text-center p-3">
            <div class="stat-label">Active Bookings</div>
            <div class="stat-value"><?= $totalBookings ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-primary text-center p-3">
            <div class="stat-label">Total Enquiries</div>
            <div class="stat-value"><?= $totalEnquiries ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-success text-center p-3">
            <div class="stat-label">New Enquiries</div>
            <div class="stat-value"><?= $newMessages ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-warning text-center p-3">
            <div class="stat-label">Replied Enquiries</div>
            <div class="stat-value"><?= $repliedMessages ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-danger text-center p-3">
            <div class="stat-label">Awaiting Confirmation</div>
            <div class="stat-value"><?= $pendingBookings ?></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Bookings</h5>
        <a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Student</th><th>Course</th><th>Class</th><th>Status</th><th>Booked</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if ($recentBookings->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No bookings yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentBookings as $booking): ?>
                    <tr>
                        <td><?= h($booking->student?->student_name ?? '-') ?></td>
                        <td><?= h($booking->class_entity?->course?->course_name ?? '-') ?></td>
                        <td><?= h($booking->class_entity?->class_code ?? '-') ?></td>
                        <td><?= $this->Badge->status($booking->booking_status) ?></td>
                        <td><?= $booking->booking_date ? $booking->booking_date->format('j M, g:ia') : '-' ?></td>
                        <td><a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'view', $booking->booking_id]) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Enquiries</h5>
        <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>From</th><th>Enquiry</th><th>Source</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if ($recentMessages->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No messages yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentMessages as $message): ?>
                    <tr>
                        <td>
                            <?php if ($message->sender_user): ?>
                                <?= h($message->sender_user->username) ?>
                            <?php else: ?>
                                <?= h($message->sender_name ?: 'Unknown') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= h(\Cake\Utility\Text::truncate($message->subject, 40)) ?></td>
                        <td><?= h($message->source_page ?: '-') ?></td>
                        <td><?= $message->sent_at ? $message->sent_at->format('j M, g:ia') : '-' ?></td>
                        <td><?= $this->Badge->status($message->message_status) ?></td>
                        <td><a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'view', $message->message_id]) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
