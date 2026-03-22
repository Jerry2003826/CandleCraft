<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $originalMessage
 */
$this->assign('title', 'Reply to Message');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'view', $originalMessage->message_id]) ?>" class="btn btn-sm">&larr; Back to Message</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Original Message</h3>
    </div>
    <div class="card-body">
        <table class="detail-table">
            <tr>
                <th>From:</th>
                <td>
                    <?php if ($originalMessage->sender_user): ?>
                        <?= h($originalMessage->sender_user->username) ?>
                    <?php else: ?>
                        <?= h($originalMessage->sender_name ?: 'Unknown') ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Subject:</th>
                <td><?= h($originalMessage->subject) ?></td>
            </tr>
            <tr>
                <th>Message:</th>
                <td><?= nl2br(h($originalMessage->message_text)) ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Your Reply</h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create(null) ?>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" value="Re: <?= h($originalMessage->subject) ?>" disabled
                       style="width: 100%; padding: 10px 14px; border: 1px solid #dcdde1; border-radius: 6px; background: #f5f6fa;">
            </div>
            <div class="form-group">
                <label for="message_text">Message</label>
                <?= $this->Form->textarea('message_text', [
                    'label' => false,
                    'required' => true,
                    'placeholder' => 'Type your reply here...',
                    'rows' => 6,
                ]) ?>
            </div>
            <?= $this->Form->button(__('Send Reply'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'view', $originalMessage->message_id]) ?>"
               class="btn" style="margin-left: 8px;">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
