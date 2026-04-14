<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Message> $messages
 * @var string|null $status
 */
$this->assign('title', 'Enquiries');
?>

<div class="admin-page-header">
    <div class="admin-tabs flex-wrap">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'unread']]) ?>" class="admin-tab <?= $status === 'unread' ? 'active' : '' ?>">Unread</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'read']]) ?>" class="admin-tab <?= $status === 'read' ? 'active' : '' ?>">Read</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'replied']]) ?>" class="admin-tab <?= $status === 'replied' ? 'active' : '' ?>">Replied</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'archived']]) ?>" class="admin-tab <?= $status === 'archived' ? 'active' : '' ?>">Archived</a>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Phone</th>
                    <th>Subject</th>
                    <th>Source</th>
                    <th>Type</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($messages->isEmpty()): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No messages found.</td></tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                    <tr>
                        <td>
                            <p class="admin-table-primary-text">
                                <?php if ($message->sender_user): ?>
                                    <?= h($message->sender_user->username) ?>
                                <?php else: ?>
                                    <?= h($message->sender_name ?: 'Unknown') ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!$message->sender_user && $message->sender_email): ?>
                                <p class="admin-table-secondary-text"><?= h($message->sender_email) ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <p class="admin-table-secondary-text"><?= h($message->sender_phone ?: '-') ?></p>
                        </td>
                        <td>
                            <p class="admin-table-primary-text"><?= h(\Cake\Utility\Text::truncate($message->subject, 40)) ?></p>
                        </td>
                        <td>
                            <p class="admin-table-secondary-text"><?= h($message->source_page ?: '-') ?></p>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-neutral"><?= ucfirst(h(str_replace('_', ' ', $message->message_type))) ?></span>
                        </td>
                        <td>
                            <p class="admin-table-secondary-text"><?= $message->sent_at ? $message->sent_at->format('j M Y, g:ia') : '-' ?></p>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'admin-badge-neutral';
                                if ($message->message_status === 'unread') $statusClass = 'admin-badge-info';
                                if ($message->message_status === 'replied') $statusClass = 'admin-badge-success';
                            ?>
                            <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($message->message_status)) ?></span>
                        </td>
                        <td>
                            <div class="admin-action-links justify-content-end">
                                <a href="<?= $this->Url->build(['action' => 'view', $message->message_id]) ?>" class="admin-action-link view" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>" class="admin-action-link edit" title="Reply">
                                    <i class="bi bi-reply"></i>
                                </a>
                                <?= $this->Form->postLink('<i class="bi bi-trash"></i>', ['action' => 'delete', $message->message_id], [
                                    'class' => 'admin-action-link delete',
                                    'confirm' => __('Are you sure you want to delete this message?'),
                                    'title' => 'Delete',
                                    'escape' => false
                                ]) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-pagination">
        <?= $this->Paginator->prev('<i class="bi bi-chevron-left"></i>', ['escape' => false]) ?>
        <?= $this->Paginator->numbers(['escape' => false]) ?>
        <?= $this->Paginator->next('<i class="bi bi-chevron-right"></i>', ['escape' => false]) ?>
    </div>
</div>
