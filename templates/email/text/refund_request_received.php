Hello <?= h((string)($recipient_name ?: 'there')) ?>,

Your refund request has been submitted to CandleCraft Academy for review.

Course: <?= h((string)$class_name) ?><?php if ($class_code !== ''): ?> (<?= h((string)$class_code) ?>)<?php endif; ?>
Student: <?= h((string)($student_name ?: 'Student')) ?>
Date and time: <?= h((string)$schedule) ?>
Booking number: #<?= h((string)$booking_id) ?>
Payment amount: <?= h((string)$payment_amount_formatted) ?>

Our team will review the request and payment details. This normally takes a few business days. If we need more information, an administrator may contact you to ask about the reason for the refund.

We will send another email once the refund has been approved and submitted.

Kind regards,
CandleCraft Academy
