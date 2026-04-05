<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Message> $messages
 * @var string|null $status
 */
$this->assign('title', 'Enquiries');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <div class="btn-group btn-group-sm flex-wrap" role="group">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-primary <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'unread']]) ?>" class="btn btn-outline-primary <?= $status === 'unread' ? 'active' : '' ?>">Unread</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'read']]) ?>" class="btn btn-outline-primary <?= $status === 'read' ? 'active' : '' ?>">Read</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'replied']]) ?>" class="btn btn-outline-primary <?= $status === 'replied' ? 'active' : '' ?>">Replied</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'archived']]) ?>" class="btn btn-outline-primary <?= $status === 'archived' ? 'active' : '' ?>">Archived</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>From</th><th>Phone</th><th>Subject</th><th>Source</th><th>Type</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                <tr>
                    <td>
                        <?php if ($message->sender_user): ?>
                            <?= h($message->sender_user->username) ?>
                        <?php else: ?>
                            <?= h($message->sender_name ?: 'Unknown') ?>
                            <?php if ($message->sender_email): ?>
                                <br><small class="text-muted"><?= h($message->sender_email) ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($message->sender_phone ?: '-') ?></td>
                    <td><?= h(\Cake\Utility\Text::truncate($message->subject, 50)) ?></td>
                    <td><?= h($message->source_page ?: '-') ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst(h(str_replace('_', ' ', $message->message_type))) ?></span></td>
                    <td><?= $message->sent_at ? $message->sent_at->format('j M Y, g:ia') : '-' ?></td>
                    <td><?= $this->Badge->status($message->message_status) ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= $this->Url->build(['action' => 'view', $message->message_id]) ?>" class="btn btn-outline-primary">View</a>
                            <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>" class="btn btn-outline-success">Reply</a>
                            <?= $this->Form->postLink('Delete', ['action' => 'delete', $message->message_id], [
                                'confirm' => __('Are you sure you want to delete this message?'),
                                'class' => 'btn btn-outline-danger',
                            ]) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-center">
        <ul class="pagination mb-0">
            <?= $this->Paginator->prev('< Previous') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next >') ?>
        </ul>
    </div>
</div>
