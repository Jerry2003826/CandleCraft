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
 * @var int $pendingAccountRequestCount
 * @var \Cake\ORM\ResultSet $recentMessages
 * @var \Cake\ORM\ResultSet $recentBookings
 * @var \Cake\ORM\ResultSet $pendingAccountRequests
 * @var array<string, \App\Model\Entity\User> $existingUsersByEmail
 * @var array<string, \App\Model\Entity\Student> $linkedStudentsByUserId
 */
$this->assign('title', 'Dashboard');
?>

<?php if ($pendingAccountRequestCount > 0): ?>
    <div class="admin-form-card admin-request-summary mb-4" style="max-width: 100%; padding: 28px;">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
            <div>
                <div class="admin-request-summary__eyebrow" style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 12px;">
                    Customer Account Requests
                </div>
                <h2 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; color: var(--admin-text-primary); margin: 0 0 10px 0;">
                    <?= $pendingAccountRequestCount ?> request<?= $pendingAccountRequestCount !== 1 ? 's' : '' ?> waiting for account setup
                </h2>
                <p style="font-family: 'Inter', sans-serif; font-size: 15px; line-height: 1.6; color: var(--admin-text-secondary); margin: 0;">
                    These enquiries already include a portal access request. You can register the customer directly from the dashboard instead of digging through the enquiry list.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-start">
                <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'index', '?' => ['status' => 'unread']]) ?>" class="admin-btn-secondary">View Enquiries</a>
            </div>
        </div>

        <div class="d-flex flex-column gap-3 mt-4">
            <?php foreach ($pendingAccountRequests as $request): ?>
                <?php
                    $requestEmail = (string)($request->sender_email ?? '');
                    $existingUser = $requestEmail !== '' ? ($existingUsersByEmail[$requestEmail] ?? null) : null;
                    $linkedStudent = $existingUser ? ($linkedStudentsByUserId[(string)$existingUser->user_id] ?? null) : null;
                    $requesterName = (string)($request->sender_name ?: $request->sender_email ?: 'Unknown requester');
                ?>
                <div class="admin-request-item" style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: center; padding: 18px 20px; border-radius: 16px;">
                    <div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 4px;">
                            <?= h($requesterName) ?>
                        </div>
                        <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); margin-bottom: 4px;">
                            <?= h($request->sender_email ?: 'No email provided') ?>
                            <?php if ($request->sent_at): ?>
                                <span style="opacity: 0.55; margin: 0 8px;">•</span>
                                <?= h($request->sent_at->format('j M, g:ia')) ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                            <?= h(\Cake\Utility\Text::truncate((string)($request->subject ?: 'Customer portal request'), 80)) ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($linkedStudent): ?>
                            <a href="<?= $this->Url->build(['controller' => 'Students', 'action' => 'view', $linkedStudent->student_id, '?' => ['message' => $request->message_id]]) ?>" class="admin-btn-primary">
                                Open Customer
                            </a>
                        <?php elseif ($existingUser): ?>
                            <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'view', $request->message_id]) ?>" class="admin-btn-primary">
                                Review Enquiry
                            </a>
                        <?php else: ?>
                            <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'createAccount', $request->message_id]) ?>" class="admin-btn-primary">
                                Register Customer
                            </a>
                        <?php endif; ?>
                        <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'view', $request->message_id]) ?>" class="admin-btn-secondary">View Enquiry</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-header">
                <h3 class="admin-stat-title">Active Customers</h3>
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
                                    if ($booking->booking_status === 'confirmed') $statusClass = 'admin-badge-success';
                                    if ($booking->booking_status === 'pending') $statusClass = 'admin-badge-warning';
                                    if ($booking->booking_status === 'cancelled') $statusClass = 'admin-badge-danger';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>"><?= h(ucfirst((string)$booking->booking_status)) ?></span>
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
                                    if ($message->message_status === 'unread') $statusClass = 'admin-badge-info';
                                    if ($message->message_status === 'replied') $statusClass = 'admin-badge-success';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>"><?= h(ucfirst((string)$message->message_status)) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
