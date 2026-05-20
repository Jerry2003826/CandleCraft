<?php
/**
 * @var \App\View\AppView $this
 */
$classLabel = (string)$class_name;
if ($class_code !== '') {
    $classLabel .= ' (' . (string)$class_code . ')';
}
?>
<div style="margin:0; padding:0; background:#fdfaf7; font-family:Arial, Helvetica, sans-serif; color:#2f2219;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdfaf7; border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:28px 14px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px; max-width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="padding:0 0 18px; text-align:center; color:#9a641f; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase;">
                            CandleCraft Academy
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff; border:1px solid #ead7c0; border-radius:18px; padding:30px 30px 26px; box-shadow:0 12px 28px rgba(47,34,25,0.08);">
                            <h1 style="margin:0 0 12px; color:#2f2219; font-family:Georgia, serif; font-size:30px; line-height:1.2;">
                                Booking confirmed
                            </h1>
                            <p style="margin:0 0 18px; color:#6f5a49; font-size:16px; line-height:1.6;">
                                Hello <?= h((string)($recipient_name ?: 'there')) ?>, your payment has been received.
                                Please log in to your account and open the Payment Portal to view your full bill.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:0 0 22px;">
                                <tr>
                                    <td style="background:#fbf3e7; border:1px solid #ead7c0; border-radius:14px; padding:18px;">
                                        <div style="font-size:12px; font-weight:700; color:#9a641f; letter-spacing:2px; text-transform:uppercase; margin-bottom:8px;">
                                            Bill snapshot
                                        </div>
                                        <div style="font-family:Georgia, serif; color:#2f2219; font-size:26px; line-height:1.2; margin-bottom:16px;">
                                            <?= h((string)$amount_formatted) ?>
                                        </div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; color:#2f2219; font-size:14px;">
                                            <tr>
                                                <td style="padding:8px 0; color:#7a6049; font-weight:700; width:34%;">Course</td>
                                                <td style="padding:8px 0;"><?= h($classLabel) ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0; color:#7a6049; font-weight:700;">Student</td>
                                                <td style="padding:8px 0;"><?= h((string)($student_name ?: 'Student')) ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0; color:#7a6049; font-weight:700;">Date and time</td>
                                                <td style="padding:8px 0;"><?= h((string)$schedule) ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0; color:#7a6049; font-weight:700;">Teacher</td>
                                                <td style="padding:8px 0;"><?= h((string)$teacher_name) ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0; color:#7a6049; font-weight:700;">Booking number</td>
                                                <td style="padding:8px 0;">#<?= h((string)$booking_id) ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0; color:#7a6049; font-weight:700;">Payment date</td>
                                                <td style="padding:8px 0;"><?= h((string)$payment_date) ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <?php if (!empty($receipt_snapshot_content_id)): ?>
                                <p style="margin:0 0 10px; color:#6f5a49; font-size:14px;">
                                    A receipt snapshot is attached below for quick reference.
                                </p>
                                <img
                                    src="cid:<?= h((string)$receipt_snapshot_content_id) ?>"
                                    alt="Receipt snapshot for <?= h($classLabel) ?>, <?= h((string)$amount_formatted) ?>"
                                    width="640"
                                    style="display:block; width:100%; max-width:640px; height:auto; border:1px solid #ead7c0; border-radius:16px;"
                                >
                            <?php endif; ?>

                            <p style="margin:22px 0 0; color:#6f5a49; font-size:14px; line-height:1.6;">
                                If anything looks incorrect, please contact CandleCraft Academy before your class.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
