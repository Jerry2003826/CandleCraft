<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Payment $payment
 */
$this->assign('title', 'Payment Receipt');
?>

<div class="card">
    <div class="card-header">
        <h3>Payment Receipt</h3>
        <a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'index']) ?>" class="btn btn-sm">&larr; My Bookings</a>
    </div>
    <div class="card-body">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #2c3e50;">CandleCraft Academy</h2>
                <p style="color: #666;">Payment Receipt</p>
            </div>

            <div class="portal-status-row" style="padding: 12px 0; border-bottom: 1px solid #eee;">
                <div>
                    <span class="portal-status-row__label">Receipt #</span>
                    <strong>PAY-<?= str_pad((string)$payment->payment_id, 6, '0', STR_PAD_LEFT) ?></strong>
                </div>
                <div>
                    <span class="portal-status-row__label">Date</span>
                    <strong><?= $payment->payment_date ? $payment->payment_date->format('j M Y, g:ia') : '-' ?></strong>
                </div>
            </div>

            <div class="portal-status-row" style="padding: 12px 0; border-bottom: 1px solid #eee;">
                <div>
                    <span class="portal-status-row__label">Class</span>
                    <strong>
                        <?= h($payment->booking
                            && $payment->booking->class_entity
                            && $payment->booking->class_entity->course
                            ? $payment->booking->class_entity->course->course_name
                            : '-') ?>
                    </strong>
                </div>
                <div>
                    <span class="portal-status-row__label">Status</span>
                    <span class="badge badge-confirmed"><?= ucfirst(h($payment->payment_status)) ?></span>
                </div>
            </div>

            <div style="text-align: right; padding: 16px 0; font-size: 1.4em;">
                <strong>Total Paid: $<?= number_format((float)$payment->amount, 2) ?> <?= h($payment->currency_code ?? 'AUD') ?></strong>
            </div>
        </div>
    </div>
</div>
