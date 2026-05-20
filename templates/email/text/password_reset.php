Hello <?= h((string)($recipient_name ?: 'there')) ?>,

We received a request to change the password for your CandleCraft Academy account.

Please use this secure one-time link to choose a new password:
<?= h((string)$reset_url) ?>

This link can only be used once and expires in <?= h((string)$expires_in) ?>. After your password is updated, you will be sent back to the login page.

If you did not request this change, you can ignore this email and keep using your current password.
