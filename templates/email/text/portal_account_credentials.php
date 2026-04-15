Hello <?= h((string)($recipient_name ?: 'there')) ?>,

Your CandleCraft Academy portal account has been created.

Login URL: <?= h((string)$login_url) ?>
Login email: <?= h((string)$login_email) ?>
Temporary password: <?= h((string)$temporary_password) ?>

<?php if ($declared_age !== null): ?>
Declared age on request: <?= h((string)$declared_age) ?>

<?php endif; ?>
You can sign in straight away to browse courses and check your portal updates.
Booking and payment access remain locked until an administrator confirms you are 18 or older.

If you did not expect this email, please contact CandleCraft Academy.
