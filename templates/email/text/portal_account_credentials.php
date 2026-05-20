Hello <?= h((string)($recipient_name ?: 'there')) ?>,

Your CandleCraft Academy portal account has been created.

Login email: <?= h((string)$login_email) ?>
Temporary password: <?= h((string)$temporary_password) ?>
Login URL: <?= h((string)$login_url) ?>
<?php if ($declared_age !== null): ?>
Declared age: <?= h((string)$declared_age) ?>
<?php endif; ?>

For your security, please change the temporary password using this one-time link:
<?= h((string)$change_password_url) ?>

This password change link expires after 7 days.

You may sign in to browse courses and check portal updates. Booking and payment access remain locked until an administrator confirms age eligibility.

Kind regards,
CandleCraft Academy
