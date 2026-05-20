<div style="margin:0; padding:0; background:#fdfaf7; font-family:Arial, Helvetica, sans-serif; color:#2f2219;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdfaf7; border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:28px 14px;">
                <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="width:620px; max-width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="padding:0 0 18px; text-align:center; color:#9a641f; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase;">
                            CandleCraft Academy
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff; border:1px solid #ead7c0; border-radius:18px; padding:30px; box-shadow:0 12px 28px rgba(47,34,25,0.08);">
                            <div style="font-size:12px; font-weight:700; color:#9a641f; letter-spacing:2px; text-transform:uppercase; margin-bottom:10px;">
                                Portal Account Ready
                            </div>
                            <h1 style="margin:0 0 14px; color:#2f2219; font-family:Georgia, serif; font-size:30px; line-height:1.2;">
                                Welcome to CandleCraft Academy
                            </h1>
                            <p style="margin:0 0 20px; color:#6f5a49; font-size:16px; line-height:1.6;">
                                Hello <?= h((string)($recipient_name ?: 'there')) ?>, your CandleCraft Academy portal account has been created.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fbf3e7; border:1px solid #ead7c0; border-radius:14px; border-collapse:collapse; margin:0 0 22px;">
                                <tr>
                                    <td style="padding:15px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700; width:34%;">Login email</td>
                                    <td style="padding:15px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;"><?= h((string)$login_email) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:15px 18px; color:#7a6049; font-weight:700;">Temporary password</td>
                                    <td style="padding:15px 18px; color:#2f2219; font-weight:700;"><?= h((string)$temporary_password) ?></td>
                                </tr>
                                <?php if ($declared_age !== null): ?>
                                    <tr>
                                        <td style="padding:15px 18px; border-top:1px solid #ead7c0; color:#7a6049; font-weight:700;">Declared age</td>
                                        <td style="padding:15px 18px; border-top:1px solid #ead7c0; color:#2f2219;"><?= h((string)$declared_age) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>

                            <p style="margin:0 0 18px; color:#6f5a49; font-size:15px; line-height:1.6;">
                                For your security, please change the temporary password using the one-time link below. The link expires after 7 days.
                            </p>
                            <p style="margin:0 0 22px;">
                                <a href="<?= h((string)$change_password_url) ?>" style="display:inline-block; background:#d8a15a; color:#2f2219; text-decoration:none; border-radius:999px; padding:12px 22px; font-weight:700;">
                                    Change temporary password
                                </a>
                            </p>
                            <p style="margin:0 0 20px;">
                                <a href="<?= h((string)$login_url) ?>" style="color:#9a641f; font-weight:700; text-decoration:none;">
                                    Log in to your portal account
                                </a>
                            </p>
                            <p style="margin:0; color:#6f5a49; font-size:14px; line-height:1.6;">
                                You may sign in to browse courses and check portal updates. Booking and payment access remain locked until an administrator confirms age eligibility.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
