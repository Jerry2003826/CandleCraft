<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Message> $messages
 * @var string|null $status
 */
$this->assign('title', 'Enquiries');
$messageItems = method_exists($messages, 'items') ? $messages->items() : $messages;
$currentUrl = $this->request->getRequestTarget();
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
                    <th>Enquiry Type</th>
                    <th>Source</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($messageItems->isEmpty()): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No enquiries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($messageItems as $message): ?>
                    <?php
                        $isAccountRequest = $message->source_page === 'account-request';
                        $viewUrl = $this->Url->build([
                            'action' => 'view',
                            $message->message_id,
                            '?' => ['return_url' => $currentUrl],
                        ]);
                    ?>
                    <tr class="admin-clickable-row" data-href="<?= h($viewUrl) ?>" tabindex="0" role="link" aria-label="Open enquiry from <?= h($message->sender_name ?: $message->sender_email ?: 'Unknown') ?>">
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
        <?php if ($isAccountRequest): ?>
            <span class="admin-badge admin-badge-info">Account Request</span>
        <?php else: ?>
            <span class="admin-badge admin-badge-neutral"><?= h(ucfirst($message->subject ?: 'General')) ?></span>
        <?php endif; ?>
    </td>
    <td>
        <p class="admin-table-secondary-text"><?= h($message->source_page ?: '-') ?></p>
    </td>
    <td>
        <p class="admin-table-secondary-text">
            <?= $message->sent_at ? h($message->sent_at->format('j M Y, g:ia')) : '-' ?>
        </p>
    </td>
    <td>
        <?php 
            $statusClass = 'admin-badge-neutral';
            if ($message->message_status === 'unread') $statusClass = 'admin-badge-info';
            if ($message->message_status === 'replied') $statusClass = 'admin-badge-success';
            if ($message->message_status === 'archived') $statusClass = 'admin-badge-warning';
        ?>
        <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($message->message_status)) ?></span>
    </td>
                    <td>
                        <div class="admin-action-links justify-content-end">
                            <a href="<?= h($viewUrl) ?>" class="admin-action-link view" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($isAccountRequest): ?>
                                <a href="<?= $this->Url->build(['action' => 'createAccount', $message->message_id]) ?>" class="admin-action-link edit" title="Create Account">
                                    <i class="bi bi-person-plus"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($message->message_status !== 'archived'): ?>
                                <?php if ($message->message_status !== 'unread'): ?>
                                    <?= $this->Form->create(null, [
                                        'url' => [
                                            'action' => 'markUnread',
                                            $message->message_id,
                                            '?' => [
                                                'return_url' => $currentUrl,
                                                'redirect' => 'return_url',
                                            ],
                                        ],
                                        'class' => 'd-inline m-0',
                                    ]) ?>
                                        <?= $this->Form->button('<i class="bi bi-circle"></i>', [
                                            'class' => 'admin-action-link view',
                                            'type' => 'submit',
                                            'title' => 'Mark as Unread',
                                            'escapeTitle' => false,
                                        ]) ?>
                                    <?= $this->Form->end() ?>
                                <?php endif; ?>
                                <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>" class="admin-action-link edit" title="Reply">
                                    <i class="bi bi-reply"></i>
                                </a>
                                <?= $this->Form->create(null, ['url' => ['action' => 'archive', $message->message_id], 'class' => 'd-inline m-0']) ?>
                                    <?= $this->Form->button('<i class="bi bi-archive"></i>', [
                                        'class' => 'admin-action-link edit',
                                        'type' => 'submit',
                                        'title' => 'Archive',
                                        'onclick' => "return confirm('Archive this enquiry?');",
                                        'escapeTitle' => false,
                                    ]) ?>
                                <?= $this->Form->end() ?>
                            <?php else: ?>
                                <?= $this->Form->create(null, ['url' => ['action' => 'restore', $message->message_id], 'class' => 'd-inline m-0']) ?>
                                    <?= $this->Form->button('<i class="bi bi-arrow-counterclockwise"></i>', [
                                        'class' => 'admin-action-link view',
                                        'type' => 'submit',
                                        'title' => 'Restore',
                                        'escapeTitle' => false,
                                    ]) ?>
                                <?= $this->Form->end() ?>
                                <?= $this->Form->create(null, ['url' => ['action' => 'delete', $message->message_id], 'class' => 'd-inline m-0']) ?>
                                    <?= $this->Form->button('<i class="bi bi-trash"></i>', [
                                        'class' => 'admin-action-link delete',
                                        'type' => 'submit',
                                        'title' => 'Delete',
                                        'onclick' => "return confirm('Delete this archived enquiry permanently?');",
                                        'escapeTitle' => false,
                                    ]) ?>
                                <?= $this->Form->end() ?>
                            <?php endif; ?>
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

<style>
    .admin-clickable-row {
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.admin-clickable-row[data-href]').forEach(function (row) {
        function openRow(event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            window.location.href = row.dataset.href;
        }

        row.addEventListener('click', openRow);
        row.addEventListener('keydown', function (event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            window.location.href = row.dataset.href;
        });
    });
});
</script>
