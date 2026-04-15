<?php
/**
 * @var string $recipient_name
 * @var string $reply_message
 * @var string $original_subject
 * @var string $original_message
 * @var string $sent_by_name
 */
?>
<p>Hello <?= h($recipient_name ?: 'there') ?>,</p>

<p><?= h($sent_by_name ?: 'CandleCraft Academy') ?> has replied to your enquiry.</p>

<div style="padding: 16px; border-radius: 12px; background: #f7f3ec; border: 1px solid #e4d7c2; margin: 20px 0;">
    <p style="margin: 0; white-space: pre-wrap; line-height: 1.7; color: #2f2219;"><?= h($reply_message) ?></p>
</div>

<p style="margin-bottom: 8px;"><strong>Your original enquiry:</strong></p>
<div style="padding: 16px; border-radius: 12px; background: #fbfaf8; border: 1px solid #eadfce;">
    <p style="margin: 0 0 8px 0; color: #7b6852;"><strong>Subject:</strong> <?= h($original_subject) ?></p>
    <p style="margin: 0; white-space: pre-wrap; line-height: 1.7; color: #4a3a2d;"><?= h($original_message) ?></p>
</div>

<p style="margin-top: 24px;">Kind regards,<br>CandleCraft Academy</p>
