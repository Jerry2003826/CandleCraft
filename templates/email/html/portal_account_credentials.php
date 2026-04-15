<p>Hello <?= h((string)($recipient_name ?: 'there')) ?>,</p>

<p>Your CandleCraft Academy portal account has been created.</p>

<p>
    <strong>Login URL:</strong>
    <a href="<?= h((string)$login_url) ?>"><?= h((string)$login_url) ?></a><br>
    <strong>Login email:</strong> <?= h((string)$login_email) ?><br>
    <strong>Temporary password:</strong> <?= h((string)$temporary_password) ?>
</p>

<?php if ($declared_age !== null): ?>
    <p><strong>Declared age on request:</strong> <?= h((string)$declared_age) ?></p>
<?php endif; ?>

<p>
    You can sign in straight away to browse courses and check your portal updates.
    Booking and payment access remain locked until an administrator confirms you are 18 or older.
</p>

<p>If you did not expect this email, please contact CandleCraft Academy.</p>
