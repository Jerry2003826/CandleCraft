<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Payment $payment
 */
$this->assign('title', 'Payment Receipt');
?>

<div class="mb-3">
    <a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> My Schedule</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payment Receipt</h5>
    </div>
    <div class="card-body">
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="text-center mb-4">
                <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                <h4 class="mt-2">CandleCraft Academy</h4>
                <p class="text-muted">Payment Receipt</p>
            </div>

            <table class="table">
                <tbody>
                    <tr>
                        <th class="text-muted" style="width: 40%;">Receipt #</th>
                        <td><strong>PAY-<?= str_pad((string)$payment->payment_id, 6, '0', STR_PAD_LEFT) ?></strong></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Date</th>
                        <td><?= $payment->payment_date ? $payment->payment_date->format('j M Y, g:ia') : '-' ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Course</th>
                        <td><?= h($payment->booking
                            && $payment->booking->class_entity
                            && $payment->booking->class_entity->course
                            ? $payment->booking->class_entity->course->course_name
                            : '-') ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Status</th>
                        <td><span class="badge badge-confirmed"><?= ucfirst(h($payment->payment_status)) ?></span></td>
                    </tr>
                    <?php if (!empty($payment->stripe_invoice_pdf_url) || !empty($payment->stripe_receipt_url)): ?>
                        <tr>
                            <th class="text-muted">Stripe Documents</th>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if (!empty($payment->stripe_invoice_pdf_url)): ?>
                                        <a href="<?= h($payment->stripe_invoice_pdf_url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Download Invoice</a>
                                    <?php endif; ?>
                                    <?php if (!empty($payment->stripe_receipt_url)): ?>
                                        <a href="<?= h($payment->stripe_receipt_url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Stripe Receipt</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th>Total Paid</th>
                        <td><strong style="font-size: 1.3em;">$<?= number_format((float)$payment->amount, 2) ?> <?= h($payment->currency_code ?? 'AUD') ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
