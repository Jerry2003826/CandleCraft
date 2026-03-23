<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Message> $messages
 * @var string|null $status
 */
$this->assign('title', 'Enquiries');
?>

<div class="toolbar">
    <div class="filters">
        <span>Filter enquiries by status:</span>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>"
           class="btn btn-sm <?= !$status ? 'btn-primary' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'unread']]) ?>"
           class="btn btn-sm <?= $status === 'unread' ? 'btn-primary' : '' ?>">Unread</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'read']]) ?>"
           class="btn btn-sm <?= $status === 'read' ? 'btn-primary' : '' ?>">Read</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'replied']]) ?>"
           class="btn btn-sm <?= $status === 'replied' ? 'btn-primary' : '' ?>">Replied</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'archived']]) ?>"
           class="btn btn-sm <?= $status === 'archived' ? 'btn-primary' : '' ?>">Archived</a>
    </div>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>From</th>
                <th>Phone</th>
                <th>Subject</th>
                <th>Source</th>
                <th>Type</th>
                <th>Received</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $message): ?>
                <tr>
                    <td>
                        <?php if ($message->sender_user): ?>
                            <?= h($message->sender_user->username) ?>
                        <?php else: ?>
                            <?= h($message->sender_name ?: 'Unknown') ?>
                            <?php if ($message->sender_email): ?>
                                <br><small style="color: #7f8c8d;"><?= h($message->sender_email) ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($message->sender_phone ?: '-') ?></td>
                    <td><?= h(\Cake\Utility\Text::truncate($message->subject, 50)) ?></td>
                    <td><?= h($message->source_page ?: '-') ?></td>
                    <td><span class="badge"><?= ucfirst(h(str_replace('_', ' ', $message->message_type))) ?></span></td>
                    <td><?= $message->sent_at ? $message->sent_at->format('j M Y, g:ia') : '-' ?></td>
                    <td>
                        <span class="badge badge-<?= h($message->message_status) ?>">
                            <?= ucfirst(h($message->message_status)) ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="<?= $this->Url->build(['action' => 'view', $message->message_id]) ?>"
                           class="btn btn-sm btn-primary">View</a>
                        <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>"
                           class="btn btn-sm btn-success">Reply</a>
                        <?= $this->Form->postLink(
                            'Delete',
                            ['action' => 'delete', $message->message_id],
                            [
                                'confirm' => __('Are you sure you want to delete this message?'),
                                'class' => 'btn btn-sm btn-danger',
                            ]
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?= $this->Paginator->prev('< Previous') ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next('Next >') ?>
    </div>
</div>
