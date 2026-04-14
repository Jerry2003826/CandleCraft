<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 * @var \Cake\ORM\ResultSet $replies
 */
$this->assign('title', 'View Message');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Messages</a>
    <div class="btn-group btn-group-sm">
        <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>" class="btn btn-outline-success">Reply</a>
        <?= $this->Form->postLink('Delete', ['action' => 'delete', $message->message_id], [
            'confirm' => __('Are you sure you want to delete this message?'),
            'class' => 'btn btn-outline-danger',
        ]) ?>
    </div>
</div>

<?php
$isDeclared18 = str_contains($message->message_text ?? '', '[AGE DECLARATION: 18+]');
$isDeclaredUnder18 = str_contains($message->message_text ?? '', '[AGE DECLARATION: Under 18]');
$hasAgeDeclaration = $isDeclared18 || $isDeclaredUnder18;
?>

<?php if ($hasAgeDeclaration): ?>
    <div class="alert <?= $isDeclared18 ? 'alert-success' : 'alert-secondary' ?> d-flex align-items-center justify-content-between mb-3">
        <div>
            <i class="bi <?= $isDeclared18 ? 'bi-shield-check' : 'bi-info-circle' ?> me-2"></i>
            <strong>Age Declaration:</strong>
            <?php if ($isDeclared18): ?>
                Customer self-declared they are <strong>18 years or older</strong>.
            <?php else: ?>
                Customer indicated they are <strong>under 18</strong>.
            <?php endif; ?>
        </div>
        <a href="<?= $this->Url->build(['controller' => 'Students', 'action' => 'add']) ?>" class="btn btn-sm btn-success">
            <i class="bi bi-person-plus me-1"></i>Create Account
        </a>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($message->subject) ?></h5>
        <?= $this->Badge->status($message->message_status) ?>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th class="text-end text-muted" style="width:150px">From:</th><td>
                <?php if ($message->sender_user): ?>
                    <?= h($message->sender_user->username) ?> (<?= h($message->sender_user->email) ?>)
                <?php else: ?>
                    <?= h($message->sender_name ?: 'Unknown') ?>
                    <?php if ($message->sender_email): ?> (<?= h($message->sender_email) ?>)<?php endif; ?>
                <?php endif; ?>
            </td></tr>
            <tr><th class="text-end text-muted">Type:</th><td><?= ucfirst(h(str_replace('_', ' ', $message->message_type))) ?></td></tr>
            <tr><th class="text-end text-muted">Phone:</th><td><?= h($message->sender_phone ?: '-') ?></td></tr>
            <tr><th class="text-end text-muted">Source page:</th><td><?= h($message->source_page ?: '-') ?></td></tr>
            <tr><th class="text-end text-muted">Received:</th><td><?= $message->sent_at ? $message->sent_at->format('j M Y, g:ia') : '-' ?></td></tr>
            <?php if ($hasAgeDeclaration): ?>
                <tr><th class="text-end text-muted">Age (18+):</th><td>
                    <?php if ($isDeclared18): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Self-declared 18+</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Under 18</span>
                    <?php endif; ?>
                </td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Message</h5></div>
    <div class="card-body">
        <div class="message-thread">
            <div class="message-bubble">
                <div class="meta">
                    <strong>
                        <?php if ($message->sender_user): ?><?= h($message->sender_user->username) ?>
                        <?php else: ?><?= h($message->sender_name ?: 'Unknown') ?><?php endif; ?>
                    </strong>
                    &mdash; <?= $message->sent_at ? $message->sent_at->format('j M Y, g:ia') : '' ?>
                </div>
                <?php
                $displayText = $message->message_text;
                $displayText = str_replace(['[AGE DECLARATION: 18+]', '[AGE DECLARATION: Under 18]'], '', $displayText);
                $displayText = ltrim($displayText, "\n");
                ?>
                <div class="body"><?= nl2br(h($displayText)) ?></div>
            </div>

            <?php foreach ($replies as $reply): ?>
                <div class="message-bubble bg-light">
                    <div class="meta">
                        <strong>
                            <?php if ($reply->sender_user): ?><?= h($reply->sender_user->username) ?>
                            <?php else: ?>Admin<?php endif; ?>
                        </strong>
                        &mdash; <?= $reply->sent_at ? $reply->sent_at->format('j M Y, g:ia') : '' ?>
                    </div>
                    <div class="body"><?= nl2br(h($reply->message_text)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
