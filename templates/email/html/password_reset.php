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
                                Secure Account Access
                            </div>
                            <h1 style="margin:0 0 14px; color:#2f2219; font-family:Georgia, serif; font-size:30px; line-height:1.2;">
                                Reset your password
                            </h1>
                            <p style="margin:0 0 18px; color:#6f5a49; font-size:16px; line-height:1.6;">
                                Hello <?= h((string)($recipient_name ?: 'there')) ?>, we received a request to change the password for your CandleCraft Academy account.
                            </p>
                            <p style="margin:0 0 22px;">
                                <a href="<?= h((string)$reset_url) ?>" style="display:inline-block; background:#d8a15a; color:#2f2219; text-decoration:none; border-radius:999px; padding:12px 22px; font-weight:700;">
                                    Choose a new password
                                </a>
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fbf3e7; border:1px solid #ead7c0; border-radius:14px; border-collapse:collapse; margin:0 0 20px;">
                                <tr>
                                    <td style="padding:16px 18px; color:#6f5a49; font-size:14px; line-height:1.6;">
                                        This secure link can only be used once and expires in <?= h((string)$expires_in) ?>. After your password is updated, you will be sent back to the login page.
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0; color:#6f5a49; font-size:14px; line-height:1.6;">
                                If you did not request this change, you can ignore this email and keep using your current password.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
