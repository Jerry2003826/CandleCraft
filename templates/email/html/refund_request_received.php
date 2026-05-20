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
                                Refund Request Received
                            </div>
                            <h1 style="margin:0 0 14px; color:#2f2219; font-family:Georgia, serif; font-size:30px; line-height:1.2;">
                                We have received your refund request
                            </h1>
                            <p style="margin:0 0 20px; color:#6f5a49; font-size:16px; line-height:1.6;">
                                Hello <?= h((string)($recipient_name ?: 'there')) ?>, your refund request has been submitted to CandleCraft Academy for review.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fbf3e7; border:1px solid #ead7c0; border-radius:14px; border-collapse:collapse; margin:0 0 22px;">
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700; width:34%;">Course</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;"><?= h($classLabel) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700;">Student</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;"><?= h((string)($student_name ?: 'Student')) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700;">Date and time</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;"><?= h((string)$schedule) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#7a6049; font-weight:700;">Booking number</td>
                                    <td style="padding:14px 18px; border-bottom:1px solid #ead7c0; color:#2f2219;">#<?= h((string)$booking_id) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px; color:#7a6049; font-weight:700;">Payment amount</td>
                                    <td style="padding:14px 18px; color:#2f2219;"><?= h((string)$payment_amount_formatted) ?></td>
                                </tr>
                            </table>

                            <p style="margin:0 0 14px; color:#6f5a49; font-size:15px; line-height:1.6;">
                                Our team will review the request and payment details. This normally takes a few business days. If we need more information, an administrator may contact you to ask about the reason for the refund.
                            </p>
                            <p style="margin:0; color:#6f5a49; font-size:14px; line-height:1.6;">
                                We will send another email once the refund has been approved and submitted.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
