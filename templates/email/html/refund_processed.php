<?php
$classLabel = (string)$class_name;
if ($class_code !== '') {
    $classLabel .= ' (' . (string)$class_code . ')';
}
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
                                Refund Processed
                            </div>
                            <h1 style="margin:0 0 14px; color:#2f2219; font-family:Georgia, serif; font-size:30px; line-height:1.2;">
                                Your refund has been approved
                            </h1>
                            <p style="margin:0 0 20px; color:#6f5a49; font-size:16px; line-height:1.6;">
                                Hello <?= h((string)($recipient_name ?: 'there')) ?>, your refund has been approved and submitted back to the original payment method.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fbf3e7; border:1px solid #ead7c0; border-radius:14px; border-collapse:collapse; margin:0 0 22px;">
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700; width:34%;">Course</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;"><?= h($classLabel) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700;">Refund amount</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219; font-weight:700;"><?= h((string)$refund_amount_formatted) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700;">Booking number</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;">#<?= h((string)$booking_id) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700;">Refund date</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;"><?= h((string)$refund_date) ?></td>
                                </tr>
                                <?php if ($refund_reference !== ''): ?>
                                    <tr>
                                        <td style="padding:14px 18px; color:#7a6049; font-weight:700;">Refund reference</td>
                                        <td style="padding:14px 18px; color:#2f2219;"><?= h((string)$refund_reference) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>

                            <p style="margin:0 0 14px; color:#6f5a49; font-size:15px; line-height:1.6;">
                                The funds are usually returned to the original payment method within <?= h((string)$business_days) ?>. Your bank or card provider may take a little longer to show the refund on your statement.
                            </p>
                            <p style="margin:0; color:#6f5a49; font-size:14px; line-height:1.6;">
                                Thank you for your patience while we reviewed the request.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
