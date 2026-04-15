<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 * @var iterable $paymentProfiles
 * @var \App\Model\Entity\PaymentProfile $paymentProfile
 * @var array<string, string> $preferredPaymentMethods
 */
$this->assign('title', 'Payment Portal');

$bookingList = is_object($bookings) && method_exists($bookings, 'toList') ? $bookings->toList() : (array)$bookings;
$profileList = is_object($paymentProfiles) && method_exists($paymentProfiles, 'toList') ? $paymentProfiles->toList() : (array)$paymentProfiles;
$activeProfiles = array_values(array_filter($profileList, fn($profile) => $profile->profile_status === 'active'));
$archivedProfiles = array_values(array_filter($profileList, fn($profile) => $profile->profile_status === 'archived'));
$editingExistingProfile = !$paymentProfile->isNew() && !empty($paymentProfile->payment_profile_id);
$profileSaveUrl = $editingExistingProfile
    ? ['action' => 'saveProfile', $paymentProfile->payment_profile_id]
    : ['action' => 'saveProfile'];
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="admin-form-title m-0" style="font-size: 18px;">Payment History &amp; Payment Details</h2>
        <p style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); margin: 6px 0 0 0;">
            Manage your billing profile and review receipts for paid class bookings.
        </p>
    </div>
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Payment Details
    </a>
</div>

<div class="row g-4">
    <div class="col-xl-7 d-flex flex-column gap-4">
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h3 class="admin-table-title">Payment History</h3>
                <span class="admin-table-subtitle" style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary);">
                    Receipts appear once a booking has been paid.
                </span>
            </div>

            <?php if ($bookingList === []): ?>
                <div class="text-center py-5">
                    <i class="bi bi-credit-card" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
                    <p class="mt-3 mb-2" style="color: var(--admin-text-secondary);">No class bookings are available for payment yet.</p>
                    <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="admin-btn-primary mt-2">Open Booking System</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Class</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Booking</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookingList as $booking): ?>
                                <?php
                                    $latestPayment = null;
                                    $hasPaidRecord = false;
                                    foreach ($booking->payments ?? [] as $payment) {
                                        $latestPayment = $payment;
                                        if ($payment->payment_status === 'paid') {
                                            $hasPaidRecord = true;
                                        }
                                    }
                                    $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord;
                                    $paymentBadgeClass = $isPaid ? 'admin-badge-success' : 'admin-badge-warning';
                                    $paymentBadgeLabel = $isPaid ? 'Paid' : 'Awaiting payment';
                                    $bookingBadgeClass = match ($booking->booking_status) {
                                        'confirmed', 'completed' => 'admin-badge-success',
                                        'pending' => 'admin-badge-warning',
                                        'cancelled' => 'admin-badge-danger',
                                        default => 'admin-badge-neutral',
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <p class="admin-table-primary-text"><?= h($booking->class_entity?->course?->course_name ?? '-') ?></p>
                                    </td>
                                    <td>
                                        <p class="admin-table-primary-text"><?= h($booking->class_entity?->class_code ?? '-') ?></p>
                                        <p class="admin-table-secondary-text">
                                            <?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('j M Y, g:ia') : '-' ?>
                                        </p>
                                    </td>
                                    <td>
                                        <p class="admin-table-primary-text">$<?= number_format((float)$booking->price_at_booking, 2) ?></p>
                                    </td>
                                    <td>
                                        <span class="admin-badge <?= $paymentBadgeClass ?>"><?= h($paymentBadgeLabel) ?></span>
                                    </td>
                                    <td>
                                        <span class="admin-badge <?= $bookingBadgeClass ?>"><?= h(ucfirst((string)$booking->booking_status)) ?></span>
                                    </td>
                                    <td>
                                        <div class="admin-action-links justify-content-end">
                                            <?php if (!$isPaid && in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                                <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="admin-btn-primary" style="padding: 6px 12px; font-size: 12px;">Pay Now</a>
                                            <?php elseif ($isPaid && $latestPayment): ?>
                                                <a href="<?= $this->Url->build(['action' => 'receipt', $latestPayment->payment_id]) ?>" class="admin-action-link view" style="padding: 6px 12px; height: auto; width: auto; font-size: 12px;">Receipt</a>
                                            <?php else: ?>
                                                <span class="text-muted small">No action</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-5 d-flex flex-column gap-4">
        <div class="admin-form-card" style="max-width: 100%; padding: 24px;">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="admin-form-title" style="font-size: 18px; margin: 0;">Saved Payment Details</h3>
                    <p style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); margin: 8px 0 0 0;">
                        CandleCraft stores billing and contact details only. Card numbers and CVC values are never stored locally.
                    </p>
                </div>
            </div>

            <?php if ($activeProfiles === []): ?>
                <div style="padding: 18px; border-radius: 12px; background: var(--admin-search-bg); border: 1px dashed var(--admin-card-border); color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px;">
                    No payment details have been saved yet.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($activeProfiles as $profile): ?>
                        <div style="padding: 18px; border-radius: 14px; border: 1px solid var(--admin-card-border); background: var(--admin-search-bg);">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <div>
                                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                                        <?= h($profile->billing_name) ?>
                                    </div>
                                    <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); margin-top: 4px;">
                                        <?= h($profile->billing_email) ?>
                                    </div>
                                </div>
                                <?php if ($profile->is_default): ?>
                                    <span class="admin-badge admin-badge-success">Default</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); line-height: 1.6;">
                                <?= h(ucwords(str_replace('_', ' ', (string)$profile->preferred_payment_method))) ?>
                                <?php if ($profile->billing_phone): ?>
                                    <span style="opacity: 0.6;">•</span> <?= h($profile->billing_phone) ?>
                                <?php endif; ?>
                                <br>
                                <?= h(trim(implode(', ', array_filter([
                                    $profile->billing_address_line1,
                                    $profile->billing_address_line2,
                                    $profile->billing_city,
                                    $profile->billing_state,
                                    $profile->billing_postcode,
                                    $profile->billing_country,
                                ])))) ?: 'No billing address saved yet.' ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="<?= $this->Url->build(['action' => 'index', '?' => ['profile' => $profile->payment_profile_id]]) ?>" class="admin-action-link edit" style="padding: 6px 12px; height: auto; width: auto; font-size: 12px;">
                                    Edit
                                </a>
                                <?php if (!$profile->is_default): ?>
                                    <?= $this->Form->postLink(
                                        'Set Default',
                                        ['action' => 'setDefaultProfile', $profile->payment_profile_id],
                                        ['class' => 'admin-action-link view', 'style' => 'padding: 6px 12px; height: auto; width: auto; font-size: 12px;']
                                    ) ?>
                                <?php endif; ?>
                                <?= $this->Form->postLink(
                                    'Archive',
                                    ['action' => 'archiveProfile', $profile->payment_profile_id],
                                    [
                                        'class' => 'admin-action-link delete',
                                        'style' => 'padding: 6px 12px; height: auto; width: auto; font-size: 12px;',
                                        'confirm' => 'Archive these payment details?',
                                    ]
                                ) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($archivedProfiles !== []): ?>
                <div style="margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--admin-card-border);">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 13px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 10px;">
                        Archived
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($archivedProfiles as $profile): ?>
                            <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                                <?= h($profile->billing_name) ?> · <?= h($profile->billing_email) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-form-card" style="max-width: 100%; padding: 24px;">
            <h3 class="admin-form-title" style="font-size: 18px; margin-bottom: 16px;">
                <?= $editingExistingProfile ? 'Update Payment Details' : 'Add Payment Details' ?>
            </h3>

            <?= $this->Form->create($paymentProfile, [
                'url' => $profileSaveUrl,
                'templates' => ['inputContainer' => '{{content}}'],
            ]) ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="admin-form-label">Billing Name</label>
                        <?= $this->Form->control('billing_name', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Billing Email</label>
                        <?= $this->Form->control('billing_email', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Billing Phone</label>
                        <?= $this->Form->control('billing_phone', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-md-6">
                        <label class="admin-form-label">Preferred Payment Method</label>
                        <?= $this->Form->control('preferred_payment_method', [
                            'label' => false,
                            'options' => $preferredPaymentMethods,
                            'class' => 'admin-form-select',
                        ]) ?>
                    </div>
                    <div class="col-12">
                        <label class="admin-form-label">Billing Address Line 1</label>
                        <?= $this->Form->control('billing_address_line1', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-12">
                        <label class="admin-form-label">Billing Address Line 2</label>
                        <?= $this->Form->control('billing_address_line2', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-md-4">
                        <label class="admin-form-label">City</label>
                        <?= $this->Form->control('billing_city', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-md-4">
                        <label class="admin-form-label">State</label>
                        <?= $this->Form->control('billing_state', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-md-4">
                        <label class="admin-form-label">Postcode</label>
                        <?= $this->Form->control('billing_postcode', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-12">
                        <label class="admin-form-label">Country</label>
                        <?= $this->Form->control('billing_country', ['label' => false, 'class' => 'admin-form-input']) ?>
                    </div>
                    <div class="col-12">
                        <label style="display: inline-flex; align-items: center; gap: 10px; font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary);">
                            <?= $this->Form->checkbox('is_default', ['hiddenField' => true]) ?>
                            Set as default payment details
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap mt-4">
                    <?= $this->Form->button($editingExistingProfile ? 'Update Payment Details' : 'Save Payment Details', ['class' => 'admin-btn-primary']) ?>
                    <?php if ($editingExistingProfile): ?>
                        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary" style="text-decoration: none;">Cancel</a>
                    <?php endif; ?>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
