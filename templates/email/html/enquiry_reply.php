<?php
/**
 * @var string $recipient_name
 * @var string $reply_message
 * @var string $original_subject
 * @var string $original_message
 * @var string $sent_by_name
 */
?>
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
                                Enquiry Response
                            </div>
                            <h1 style="margin:0 0 14px; color:#2f2219; font-family:Georgia, serif; font-size:30px; line-height:1.2;">
                                Thank you for contacting us
                            </h1>
                            <p style="margin:0 0 20px; color:#6f5a49; font-size:16px; line-height:1.6;">
                                Hello <?= h($recipient_name ?: 'there') ?>, <?= h($sent_by_name ?: 'CandleCraft Academy') ?> has replied to your enquiry.
                            </p>

                            <div style="background:#fbf3e7; border:1px solid #ead7c0; border-radius:14px; padding:18px; margin:0 0 22px;">
                                <div style="font-size:12px; font-weight:700; color:#9a641f; letter-spacing:2px; text-transform:uppercase; margin-bottom:10px;">
                                    Our response
                                </div>
                                <p style="margin:0; white-space:pre-wrap; line-height:1.7; color:#2f2219; font-size:15px;"><?= h($reply_message) ?></p>
                            </div>

                            <div style="background:#ffffff; border:1px solid #ead7c0; border-radius:14px; padding:18px;">
                                <div style="font-size:12px; font-weight:700; color:#7a6049; letter-spacing:2px; text-transform:uppercase; margin-bottom:10px;">
                                    Your original enquiry
                                </div>
                                <p style="margin:0 0 10px; color:#2f2219; font-size:14px;"><strong>Subject:</strong> <?= h($original_subject) ?></p>
                                <p style="margin:0; white-space:pre-wrap; line-height:1.7; color:#6f5a49; font-size:14px;"><?= h($original_message) ?></p>
                            </div>

                            <p style="margin:22px 0 0; color:#6f5a49; font-size:14px; line-height:1.6;">
                                Kind regards,<br>
                                <strong>CandleCraft Academy</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
