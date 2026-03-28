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
 * @var \Cake\ORM\ResultSet $recentMessages
 */
$this->assign('title', 'Dashboard');
?>

<div class="stats-row">
    <div class="stat-card enquiries">
        <div class="stat-label">Active Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
    </div>
    <div class="stat-card new-messages">
        <div class="stat-label">Active Teachers</div>
        <div class="stat-value"><?= $totalTeachers ?></div>
    </div>
    <div class="stat-card replied">
        <div class="stat-label">Upcoming Classes</div>
        <div class="stat-value"><?= $totalClasses ?></div>
    </div>
    <div class="stat-card enquiries">
        <div class="stat-label">Active Bookings</div>
        <div class="stat-value"><?= $totalBookings ?></div>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card enquiries">
        <div class="stat-label">Total Enquiries</div>
        <div class="stat-value"><?= $totalEnquiries ?></div>
    </div>
    <div class="stat-card new-messages">
        <div class="stat-label">New Enquiries</div>
        <div class="stat-value"><?= $newMessages ?></div>
    </div>
    <div class="stat-card replied">
        <div class="stat-label">Replied Enquiries</div>
        <div class="stat-value"><?= $repliedMessages ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Recent Enquiries</h3>
        <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'index']) ?>" class="btn btn-sm btn-primary">View All</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>From</th>
                <th>Enquiry</th>
                <th>Source</th>
                <th>Received</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($recentMessages->isEmpty()): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">No messages yet.</td>
                </tr>
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
                        <td>
                            <span class="badge badge-<?= h($message->message_status) ?>">
                                <?= ucfirst(h($message->message_status)) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'view', $message->message_id]) ?>"
                               class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
