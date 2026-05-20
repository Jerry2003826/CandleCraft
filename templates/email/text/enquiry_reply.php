<?php
/**
 * @var string $recipient_name
 * @var string $reply_message
 * @var string $original_subject
 * @var string $original_message
 * @var string $sent_by_name
 */
?>
Hello <?= $recipient_name ?: 'there' ?>,

Thank you for contacting CandleCraft Academy. <?= $sent_by_name ?: 'CandleCraft Academy' ?> has replied to your enquiry.

Our response:
<?= $reply_message ?>

Your original enquiry:
Subject: <?= $original_subject ?>
<?= $original_message ?>

Kind regards,
CandleCraft Academy
