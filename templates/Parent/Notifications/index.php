<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Notification> $notifications
 */
$this->assign('title', 'Notifications');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Notifications</h5>
        <?= $this->Form->postLink('<i class="bi bi-check2-all me-1"></i> Mark All Read', ['action' => 'markAllRead'], ['class' => 'btn btn-sm btn-outline-primary', 'escape' => false]) ?>
    </div>
    <div class="card-body pb-0">
        <p class="text-muted mb-3">Notifications for you and your children.</p>
    </div>
    <?php if ($notifications->isEmpty()): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell" style="font-size: 48px;"></i>
            <p class="mt-3">No notifications yet.</p>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item <?= !$notification->is_read ? 'list-group-item-info' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-secondary"><?= ucfirst(h(str_replace('_', ' ', $notification->notification_type))) ?></span>
                                <?php if (!$notification->is_read): ?>
                                    <span class="badge badge-pending">New</span>
                                <?php endif; ?>
                            </div>
                            <h6 class="mb-1"><?= h($notification->title) ?></h6>
                            <p class="text-muted small mb-1"><?= h($notification->message) ?></p>
                            <small class="text-muted"><?= $notification->created ? $notification->created->timeAgoInWords() : '' ?></small>
                        </div>
                        <?php if (!$notification->is_read): ?>
                            <div class="ms-3">
                                <?= $this->Form->postLink('<i class="bi bi-check2"></i>', ['action' => 'markRead', $notification->id], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false, 'title' => 'Mark Read']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
