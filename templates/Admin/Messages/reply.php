<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $originalMessage
 * @var string $replySubject
 */
$this->assign('title', 'Respond to Enquiry');
$returnUrl = (string)($this->request->getQuery('return_url') ?? '');
$webrootPrefix = (string)($this->request->getAttribute('webroot') ?? '/');
$normaliseReturnUrl = static function (string $url) use ($webrootPrefix): string {
    if ($url === '' || preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }

    $prefix = rtrim($webrootPrefix, '/');
    $normalised = '/' . ltrim($url, '/');
    if ($prefix !== '' && str_starts_with($normalised, $prefix . '/')) {
        return $normalised;
    }

    return $prefix . $normalised;
};
$backUrl = $returnUrl !== ''
    ? $normaliseReturnUrl($returnUrl)
    : '#';
$originalDisplayText = preg_replace(
    [
        '/^\[REQUEST TYPE:\s*[^\]]+\]\s*$/mi',
        '/^\[REQUESTED ROLE:\s*[^\]]+\]\s*$/mi',
        '/^\[SELF DECLARED 18\+:\s*[^\]]+\]\s*$/mi',
        '/^\[DECLARED AGE:\s*[^\]]+\]\s*$/mi',
        '/^\[STUDENT_NAME:\s*[^\]]+\]\s*$/mi',
        '/^\[STUDENT_DOB:\s*[^\]]+\]\s*$/mi',
        '/^\[CLASS_TYPE:\s*[^\]]+\]\s*$/mi',
    ],
    '',
    (string)$originalMessage->message_text,
);
$originalDisplayText = trim((string)$originalDisplayText);
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <a href="<?= h($backUrl) ?>" <?= $returnUrl === '' ? 'onclick="history.back(); return false;"' : '' ?> class="admin-back-link mb-0">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <h2 class="admin-form-title mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border); font-size: 20px;">Original Enquiry</h2>
    
    <div class="d-flex flex-column gap-4">
        <div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">From</div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                <?php if ($originalMessage->sender_user): ?><?= h($originalMessage->sender_user->username) ?>
                <?php else: ?><?= h($originalMessage->sender_name ?: 'Unknown') ?><?php endif; ?>
            </div>
        </div>
        <div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Reply To</div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                <?= h($originalMessage->sender_email ?: 'No email address supplied') ?>
            </div>
        </div>
        <div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Subject</div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                <?= h($originalMessage->subject) ?>
            </div>
        </div>
        <div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Message</div>
            <div style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary); line-height: 1.6; white-space: pre-wrap; background-color: var(--admin-search-bg); padding: 16px; border-radius: 8px; border: 1px solid var(--admin-card-border);"><?= h($originalDisplayText !== '' ? $originalDisplayText : 'No additional notes provided.') ?></div>
        </div>
    </div>
</div>

<div class="admin-form-card" style="max-width: 100%; padding: 32px;">
    <h2 class="admin-form-title mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border); font-size: 20px;">Email Reply</h2>
    <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin: -8px 0 24px 0;">
        Sending this form will email the customer directly and save an audit record in the enquiry thread.
    </p>
    
    <?= $this->Form->create(null, ['templates' => ['inputContainer' => '{{content}}']]) ?>
        <?= $this->Form->hidden('return_url', [
            'value' => $returnUrl !== '' ? $returnUrl : $this->Url->build(['action' => 'view', $originalMessage->message_id]),
        ]) ?>
        <div style="margin-bottom: 24px;">
            <label for="reply-subject-display" style="display: block; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-primary); margin-bottom: 8px;">Subject</label>
            <input id="reply-subject-display" type="text" value="<?= h($replySubject) ?>" disabled class="admin-form-input" style="background-color: var(--admin-search-bg); color: var(--admin-text-secondary); cursor: not-allowed; opacity: 0.8;">
        </div>
        
        <div style="margin-bottom: 32px;">
            <label for="message_text" style="display: block; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-primary); margin-bottom: 8px;">Reply message</label>
            <?= $this->Form->textarea('message_text', [
                'class' => 'admin-form-textarea',
                'required' => true, 
                'placeholder' => 'Type the email you want CandleCraft Academy to send...', 
                'rows' => 6,
                'style' => 'width: 100%; resize: vertical;'
            ]) ?>
        </div>
        
        <div class="d-flex gap-3">
            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-send me-2"></i> Send Email Reply
            </button>
            <a href="<?= h($returnUrl !== '' ? $backUrl : $this->Url->build(['action' => 'view', $originalMessage->message_id])) ?>" class="admin-btn-secondary" style="text-decoration: none;">
                Cancel
            </a>
        </div>
    <?= $this->Form->end() ?>
</div>
