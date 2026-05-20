<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Notification> $notifications
 */
$this->assign('title', 'Notifications');
$notificationList = is_object($notifications) && method_exists($notifications, 'items')
    ? $notifications->items()
    : (is_array($notifications) ? $notifications : iterator_to_array($notifications));
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <?= $this->Form->create(null, ['url' => ['action' => 'markAllRead'], 'class' => 'm-0']) ?>
        <?= $this->Form->button(
            '<i class="bi bi-check2-all me-1"></i> Mark All Read',
            ['class' => 'admin-btn-secondary', 'escapeTitle' => false]
        ) ?>
    <?= $this->Form->end() ?>
</div>

<?php if ($notificationList === []): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-bell" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
        <p class="mt-3" style="color: var(--admin-text-secondary);">No notifications yet.</p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
            <?php foreach ($notificationList as $notification): ?>
                <div style="background-color: <?= !$notification->is_read ? 'rgba(59, 130, 246, 0.05)' : 'var(--admin-card-bg)' ?>; border: 1px solid <?= !$notification->is_read ? 'rgba(59, 130, 246, 0.2)' : 'var(--admin-card-border)' ?>; border-radius: 12px; padding: 20px; transition: border-color 0.2s;">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="admin-badge admin-badge-neutral" style="padding: 2px 8px; font-size: 11px;">
                                    <?= ucfirst(h(str_replace('_', ' ', $notification->notification_type))) ?>
                                </span>
                                <?php if (!$notification->is_read): ?>
                                    <span class="admin-badge admin-badge-info" style="padding: 2px 8px; font-size: 11px;">New</span>
                                <?php endif; ?>
                            </div>
                            
                            <h4 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary); margin: 0 0 8px 0;">
                                <?= h($notification->title) ?>
                            </h4>
                            
                            <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin: 0 0 8px 0;">
                                <?= h($notification->message) ?>
                            </p>
                            
                            <span style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary);">
                                <i class="bi bi-clock me-1"></i><?= $notification->created ? $notification->created->timeAgoInWords() : '' ?>
                            </span>
                        </div>
                        
                        <?php if (!$notification->is_read): ?>
                            <div class="ms-3">
                                <?= $this->Form->create(null, ['url' => ['action' => 'markRead', $notification->id], 'class' => 'm-0']) ?>
                                    <?= $this->Form->button(
                                        '<i class="bi bi-check2"></i> Mark Read',
                                        ['class' => 'admin-action-link view', 'style' => 'padding: 6px 12px; height: auto; width: auto; font-size: 12px;', 'escapeTitle' => false, 'title' => 'Mark Read']
                                    ) ?>
                                <?= $this->Form->end() ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
<?php endif; ?>
