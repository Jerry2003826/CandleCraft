<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Payment $payment
 */
$this->assign('title', 'Payment Receipt');
?>

<a href="#" onclick="history.back(); return false;" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="admin-form-card" style="max-width: 600px;">
    <div class="text-center mb-5 mt-4">
        <div style="width: 80px; height: 80px; background-color: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 24px auto;">
            <i class="bi bi-check-circle" style="font-size: 40px; color: #10B981;"></i>
        </div>
        <h2 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; color: var(--admin-text-primary); margin: 0 0 8px 0;">
            CandleCraft Academy
        </h2>
        <p style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: var(--admin-text-secondary); margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">
            Payment Receipt
        </p>
    </div>

    <div style="background-color: var(--admin-search-bg); border-radius: 12px; padding: 24px;">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px dashed var(--admin-card-border);">
            <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Receipt #</span>
            <strong style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary);">
                PAY-<?= str_pad((string)$payment->payment_id, 6, '0', STR_PAD_LEFT) ?>
            </strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px dashed var(--admin-card-border);">
            <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Date</span>
            <strong style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary);">
                <?= $payment->payment_date ? $payment->payment_date->format('j M Y, g:ia') : '-' ?>
            </strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px dashed var(--admin-card-border);">
            <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Course</span>
            <strong style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary); text-align: right;">
                <?= h($payment->booking && $payment->booking->class_entity && $payment->booking->class_entity->course ? $payment->booking->class_entity->course->course_name : '-') ?>
            </strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">Status</span>
            <span class="admin-badge admin-badge-success" style="font-size: 12px; padding: 4px 10px;">
                <?= ucfirst(h($payment->payment_status)) ?>
            </span>
        </div>
        <?php if (!empty($payment->stripe_invoice_pdf_url) || !empty($payment->stripe_receipt_url)): ?>
            <div class="d-flex gap-2 justify-content-end mb-4">
                <?php if (!empty($payment->stripe_invoice_pdf_url)): ?>
                    <a href="<?= h($payment->stripe_invoice_pdf_url) ?>" target="_blank" rel="noopener" class="admin-action-link view" style="text-decoration: none; font-size: 13px;">Download Invoice</a>
                <?php endif; ?>
                <?php if (!empty($payment->stripe_receipt_url)): ?>
                    <a href="<?= h($payment->stripe_receipt_url) ?>" target="_blank" rel="noopener" class="admin-action-link view" style="text-decoration: none; font-size: 13px;">Stripe Receipt</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center pt-4" style="border-top: 1px solid var(--admin-card-border);">
            <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; color: var(--admin-text-primary);">Total Paid</span>
            <strong style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 24px; color: var(--admin-text-primary);">
                $<?= number_format((float)$payment->amount, 2) ?> <?= h($payment->currency_code ?? 'AUD') ?>
            </strong>
        </div>
    </div>
</div>
