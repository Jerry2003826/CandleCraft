<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Notification> $notifications
 */
$this->assign('title', 'Notifications');
?>

<div class="card">
    <div class="card-header">
        <h3>Notifications</h3>
        <?= $this->Form->postLink('Mark All Read', ['action' => 'markAllRead'], ['class' => 'btn btn-sm btn-primary']) ?>
    </div>
    <?php if ($notifications->isEmpty()): ?>
        <div class="empty-state">
            <div class="icon">&#x1F514;</div>
            <p>No notifications yet.</p>
        </div>
    <?php else: ?>
        <div class="portal-class-list">
            <?php foreach ($notifications as $notification): ?>
                <section class="portal-class-card" style="<?= !$notification->is_read ? 'border-left: 4px solid #3498db; background: #f8f9ff;' : '' ?>">
                    <div class="portal-class-card__header">
                        <div>
                            <p class="portal-class-card__eyebrow">
                                <?= ucfirst(h(str_replace('_', ' ', $notification->notification_type))) ?>
                            </p>
                            <h3><?= h($notification->title) ?></h3>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="color: #999; font-size: 0.85em;">
                                <?= $notification->created ? $notification->created->timeAgoInWords() : '' ?>
                            </span>
                            <?php if (!$notification->is_read): ?>
                                <span class="badge badge-pending">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p style="color: #555; margin: 8px 0;"><?= h($notification->message) ?></p>
                    <?php if (!$notification->is_read): ?>
                        <?= $this->Form->postLink('Mark Read', ['action' => 'markRead', $notification->id], ['class' => 'btn btn-sm']) ?>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
