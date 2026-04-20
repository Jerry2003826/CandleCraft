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
                    <tr><td colspan="8" class="text-center text-muted py-4">No enquiries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                    <?php $isAccountRequest = $message->source_page === 'account-request'; ?>
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
                            <?php if ($isAccountRequest): ?>
                                <span class="admin-badge admin-badge-info">Account Request</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge-neutral">Enquiry</span>
                            <?php endif; ?>
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
                                <a href="<?= $this->Url->build(['action' => 'view', $message->message_id]) ?>" class="admin-action-link view" title="View" aria-label="View enquiry from <?= h($message->sender_name ?: $message->sender_email ?: 'customer') ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($isAccountRequest): ?>
                                    <a href="<?= $this->Url->build(['action' => 'createAccount', $message->message_id]) ?>" class="admin-action-link edit" title="Create Account" aria-label="Create account from enquiry by <?= h($message->sender_name ?: $message->sender_email ?: 'customer') ?>">
                                        <i class="bi bi-person-plus"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>" class="admin-action-link edit" title="Reply" aria-label="Reply to enquiry from <?= h($message->sender_name ?: $message->sender_email ?: 'customer') ?>">
                                    <i class="bi bi-reply"></i>
                                </a>
                                <?= $this->Form->create(null, [
                                    'url' => ['action' => 'delete', $message->message_id],
                                    'class' => 'd-inline m-0',
                                ]) ?>
                                    <?= $this->Form->button('<i class="bi bi-trash"></i>', [
                                        'class' => 'admin-action-link delete',
                                        'type' => 'submit',
                                        'title' => 'Delete',
                                        'aria-label' => 'Delete enquiry from ' . ($message->sender_name ?: $message->sender_email ?: 'customer'),
                                        'onclick' => "return confirm('Are you sure you want to delete this enquiry?');",
                                        'escapeTitle' => false,
                                    ]) ?>
                                <?= $this->Form->end() ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-pagination">
        <?= $this->Paginator->prev('<i class="bi bi-chevron-left"></i>', ['escape' => false, 'aria-label' => 'Previous page']) ?>
        <?= $this->Paginator->numbers(['escape' => false]) ?>
        <?= $this->Paginator->next('<i class="bi bi-chevron-right"></i>', ['escape' => false, 'aria-label' => 'Next page']) ?>
    </div>
</div>
