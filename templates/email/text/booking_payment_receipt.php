Hello <?= h((string)($recipient_name ?: 'there')) ?>,

Your CandleCraft Academy booking has been confirmed and your payment has been received.
Please log in to your account and open the Payment Portal to view your full bill.

Bill snapshot:
Course: <?= h((string)$class_name) ?><?php if ($class_code !== ''): ?> (<?= h((string)$class_code) ?>)<?php endif; ?>
Student: <?= h((string)($student_name ?: 'Student')) ?>
Date and time: <?= h((string)$schedule) ?>
Location: <?= h((string)$location) ?>
Teacher: <?= h((string)$teacher_name) ?>
Amount paid: <?= h((string)$amount_formatted) ?>
Payment date: <?= h((string)$payment_date) ?>
Booking number: #<?= h((string)$booking_id) ?>
Payment reference: <?= h((string)$payment_reference) ?>

A receipt snapshot is attached to this email for quick reference.

Thank you for booking with CandleCraft Academy.
