<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $originalMessage
 */
$this->assign('title', 'Reply to Message');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['action' => 'view', $originalMessage->message_id]) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Message</a>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Original Message</h5></div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th class="text-end text-muted" style="width:150px">From:</th><td>
                <?php if ($originalMessage->sender_user): ?><?= h($originalMessage->sender_user->username) ?>
                <?php else: ?><?= h($originalMessage->sender_name ?: 'Unknown') ?><?php endif; ?>
            </td></tr>
            <tr><th class="text-end text-muted">Subject:</th><td><?= h($originalMessage->subject) ?></td></tr>
            <tr><th class="text-end text-muted">Message:</th><td><?= nl2br(h($originalMessage->message_text)) ?></td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Your Reply</h5></div>
    <div class="card-body">
        <?= $this->Form->create(null) ?>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" value="Re: <?= h($originalMessage->subject) ?>" disabled class="form-control-plaintext">
            </div>
            <div class="mb-3">
                <label for="message_text" class="form-label">Message</label>
                <?= $this->Form->textarea('message_text', ['label' => false, 'required' => true, 'placeholder' => 'Type your reply here...', 'rows' => 6, ]) ?>
            </div>
            <?= $this->Form->button(__('Send Reply'), ['class' => 'btn btn-success']) ?>
            <a href="<?= $this->Url->build(['action' => 'view', $originalMessage->message_id]) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        <?= $this->Form->end() ?>
    </div>
</div>
