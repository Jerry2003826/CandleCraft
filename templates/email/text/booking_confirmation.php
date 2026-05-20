Hello <?= h((string)($recipient_name ?: 'there')) ?>,

We have received your CandleCraft Academy booking request.

Course: <?= h((string)$class_name) ?><?php if ($class_code !== ''): ?> (<?= h((string)$class_code) ?>)<?php endif; ?>
Student: <?= h((string)($student_name ?: 'Student')) ?>
Date and time: <?= h((string)$schedule) ?>
Location: <?= h((string)$location) ?>
Teacher: <?= h((string)$teacher_name) ?>
Booking number: #<?= h((string)$booking_id) ?>

<?php if ((float)$amount_due > 0): ?>
Amount due: AUD <?= h(number_format((float)$amount_due, 2)) ?>
Please complete payment in your portal to fully confirm this booking:
<?= h((string)$payment_url) ?>
<?php else: ?>
This booking does not require payment.
<?php endif; ?>

You can review your booking in your portal schedule:
<?= h((string)$schedule_url) ?>

Kind regards,
CandleCraft Academy
