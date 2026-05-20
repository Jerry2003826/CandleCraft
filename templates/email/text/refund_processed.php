Hello <?= h((string)($recipient_name ?: 'there')) ?>,

Your refund has been approved and submitted back to the original payment method.

Course: <?= h((string)$class_name) ?><?php if ($class_code !== ''): ?> (<?= h((string)$class_code) ?>)<?php endif; ?>
Refund amount: <?= h((string)$refund_amount_formatted) ?>
Booking number: #<?= h((string)$booking_id) ?>
Refund date: <?= h((string)$refund_date) ?>
<?php if ($refund_reference !== ''): ?>
Refund reference: <?= h((string)$refund_reference) ?>
<?php endif; ?>

The funds are usually returned to the original payment method within <?= h((string)$business_days) ?>. Your bank or card provider may take a little longer to show the refund on your statement.

Thank you for your patience while we reviewed the request.

Kind regards,
CandleCraft Academy
