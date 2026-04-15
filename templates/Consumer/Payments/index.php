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

$paidCount = 0;
$pendingCount = 0;
$pendingAmount = 0.0;

foreach ($bookingList as $booking) {
    $hasPaidRecord = false;
    foreach ($booking->payments ?? [] as $payment) {
        if ($payment->payment_status === 'paid') {
            $hasPaidRecord = true;
            break;
        }
    }

    if (in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord) {
        $paidCount++;
    } else {
        $pendingCount++;
        if (in_array($booking->booking_status, ['pending', 'confirmed'], true)) {
            $pendingAmount += (float)$booking->price_at_booking;
        }
    }
}
?>

<div class="z-billing-page">
    <div class="z-billing-header">
        <h1 class="z-billing-title">Billing</h1>
        <div class="z-billing-tabs">
            <a href="#overview" class="z-billing-tab active" data-tab="overview">Overview</a>
            <a href="#history" class="z-billing-tab" data-tab="history">Payment History</a>
            <a href="#details" class="z-billing-tab" data-tab="details">Saved Details</a>
        </div>
    </div>

    <div id="overview" class="z-billing-section active-section">
        <div class="z-billing-balance-card mb-4">
            <div class="z-billing-balance-main">
                <div class="z-billing-amount">$ <?= number_format($pendingAmount, 2) ?></div>
                <div class="z-billing-balance-divider"></div>
                <div class="z-billing-balance-details">
                    <div class="z-billing-balance-item">
                        <span class="z-billing-balance-label">Awaiting payment</span>
                        <span class="z-billing-balance-value"><?= $pendingCount ?> bookings</span>
                    </div>
                    <div class="z-billing-balance-item">
                        <span class="z-billing-balance-label">Paid receipts</span>
                        <span class="z-billing-balance-value"><?= $paidCount ?></span>
                    </div>
                </div>
            </div>
            <div class="z-billing-balance-actions">
                <a href="#details" class="z-billing-btn-primary" onclick="document.querySelector('[data-tab=\'details\']').click()">Add payment details</a>
                <a href="#history" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); text-decoration: none; margin-left: 16px;" onclick="document.querySelector('[data-tab=\'history\']').click()">View History</a>
            </div>
        </div>

        <div class="z-billing-grid">
            <a href="#history" class="z-billing-grid-card" onclick="document.querySelector('[data-tab=\'history\']').click()">
                <div class="z-billing-grid-icon"><i class="bi bi-clock-history"></i></div>
                <div class="z-billing-grid-content">
                    <h3>Billing History</h3>
                    <p>View past and current bills</p>
                </div>
                <i class="bi bi-chevron-right z-billing-grid-arrow"></i>
            </a>
            <a href="#details" class="z-billing-grid-card" onclick="document.querySelector('[data-tab=\'details\']').click()">
                <div class="z-billing-grid-icon"><i class="bi bi-credit-card"></i></div>
                <div class="z-billing-grid-content">
                    <h3>Payment Methods</h3>
                    <p>Add or change payment method</p>
                </div>
                <i class="bi bi-chevron-right z-billing-grid-arrow"></i>
            </a>
            <a href="<?= $this->Url->build(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']) ?>" class="z-billing-grid-card">
                <div class="z-billing-grid-icon"><i class="bi bi-journal-text"></i></div>
                <div class="z-billing-grid-content">
                    <h3>Order Summary</h3>
                    <p>View your course bookings</p>
                </div>
                <i class="bi bi-chevron-right z-billing-grid-arrow"></i>
            </a>
        </div>
    </div>

    <div id="history" class="z-billing-section">
        <h2 class="z-billing-section-title">Payment History</h2>
        
        <?php if ($bookingList === []): ?>
            <div class="admin-form-card text-center py-5" style="border-radius: 12px;">
                <i class="bi bi-receipt" style="font-size: 48px; color: var(--admin-text-secondary); margin-bottom: 16px; display: block;"></i>
                <p style="color: var(--admin-text-secondary); font-family: 'Inter', sans-serif;">No class bookings are available for payment yet.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-card" style="border-radius: 12px; padding: 0;">
                <table class="admin-table no-headers" style="margin: 0;">
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
                            ?>
                            <tr>
                                <td style="padding: 20px 24px;">
                                    <p class="admin-table-primary-text" style="font-size: 15px;"><?= h($booking->class_entity?->course?->course_name ?? '-') ?></p>
                                    <p class="admin-table-secondary-text" style="font-size: 13px;">
                                        <?= h($booking->class_entity?->class_code ?? '-') ?> • 
                                        <?= $booking->class_entity?->start_datetime ? $booking->class_entity->start_datetime->format('j M Y, g:ia') : '-' ?>
                                    </p>
                                </td>
                                <td style="padding: 20px 24px;">
                                    <p class="admin-table-primary-text" style="font-size: 16px; font-weight: 600;">$<?= number_format((float)$booking->price_at_booking, 2) ?></p>
                                    <span class="admin-badge <?= $paymentBadgeClass ?>" style="font-size: 11px; padding: 4px 8px;"><?= h($paymentBadgeLabel) ?></span>
                                </td>
                                <td class="text-end" style="padding: 20px 24px;">
                                    <?php if (!$isPaid && in_array($booking->booking_status, ['pending', 'confirmed'], true)): ?>
                                        <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="z-billing-btn-primary" style="padding: 8px 16px; font-size: 13px;">Pay Now</a>
                                    <?php elseif ($isPaid && $latestPayment): ?>
                                        <a href="<?= $this->Url->build(['action' => 'receipt', $latestPayment->payment_id]) ?>" class="admin-action-link view" style="text-decoration: none; font-size: 13px;">View Receipt</a>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-secondary); font-size: 13px;">No action needed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div id="details" class="z-billing-section">
        <h2 class="z-billing-section-title">Saved Payment Methods</h2>
        
        <div class="row g-4">
            <div class="col-lg-6">
                <?php if ($activeProfiles === []): ?>
                    <div class="admin-form-card text-center py-5" style="border-radius: 12px; height: 100%;">
                        <p style="color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; margin: 0;">No payment details have been saved yet.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($activeProfiles as $profile): ?>
                            <div class="admin-form-card" style="border-radius: 12px; padding: 24px;">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <strong style="display: block; font-family: 'Inter', sans-serif; font-size: 16px; color: var(--admin-text-primary); margin-bottom: 4px;"><?= h($profile->billing_name) ?></strong>
                                        <div style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);"><?= h($profile->billing_email) ?></div>
                                    </div>
                                    <?php if ($profile->is_default): ?>
                                        <span class="admin-badge admin-badge-success" style="font-size: 11px; padding: 4px 8px;">Default</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 16px; line-height: 1.6;">
                                    <div style="color: var(--admin-text-primary); font-weight: 500; margin-bottom: 4px;"><?= h(ucwords(str_replace('_', ' ', (string)$profile->preferred_payment_method))) ?></div>
                                    <div>
                                        <?= h(trim(implode(', ', array_filter([
                                            $profile->billing_address_line1,
                                            $profile->billing_address_line2,
                                            $profile->billing_city,
                                            $profile->billing_state,
                                            $profile->billing_postcode,
                                            $profile->billing_country,
                                        ])))) ?: 'No billing address saved yet.' ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-3 mt-auto pt-3" style="border-top: 1px solid var(--admin-card-border);">
                                    <a href="<?= $this->Url->build(['action' => 'index', '?' => ['profile' => $profile->payment_profile_id]]) ?>#payment-details-form" class="admin-action-link edit" style="text-decoration: none; font-size: 13px;">Edit</a>
                                    <?php if (!$profile->is_default): ?>
                                        <?= $this->Form->postLink(
                                            'Set Default',
                                            ['action' => 'setDefaultProfile', $profile->payment_profile_id],
                                            ['class' => 'admin-action-link view', 'style' => 'text-decoration: none; font-size: 13px;']
                                        ) ?>
                                    <?php endif; ?>
                                    <?= $this->Form->postLink(
                                        'Archive',
                                        ['action' => 'archiveProfile', $profile->payment_profile_id],
                                        [
                                            'class' => 'admin-action-link delete',
                                            'style' => 'text-decoration: none; font-size: 13px;',
                                            'confirm' => 'Archive these payment details?',
                                        ]
                                    ) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-6">
                <div class="admin-form-card" id="payment-details-form" style="border-radius: 12px; padding: 24px;">
                    <h3 class="admin-form-title mb-4" style="font-size: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--admin-card-border);">
                        <?= $editingExistingProfile ? 'Update Payment Details' : 'Add Payment Details' ?>
                    </h3>
                    
                    <?= $this->Form->create($paymentProfile, [
                        'url' => $profileSaveUrl,
                        'templates' => ['inputContainer' => '{{content}}'],
                    ]) ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="admin-form-label" style="font-size: 13px;">Billing Name</label>
                                <?= $this->Form->control('billing_name', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-md-6">
                                <label class="admin-form-label" style="font-size: 13px;">Billing Email</label>
                                <?= $this->Form->control('billing_email', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-12">
                                <label class="admin-form-label" style="font-size: 13px;">Preferred Payment Method</label>
                                <?= $this->Form->control('preferred_payment_method', [
                                    'label' => false,
                                    'options' => $preferredPaymentMethods,
                                    'class' => 'admin-form-select',
                                ]) ?>
                            </div>
                            <div class="col-12">
                                <label class="admin-form-label" style="font-size: 13px;">Billing Address Line 1</label>
                                <?= $this->Form->control('billing_address_line1', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-md-6">
                                <label class="admin-form-label" style="font-size: 13px;">City</label>
                                <?= $this->Form->control('billing_city', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-md-6">
                                <label class="admin-form-label" style="font-size: 13px;">State</label>
                                <?= $this->Form->control('billing_state', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-md-6">
                                <label class="admin-form-label" style="font-size: 13px;">Postcode</label>
                                <?= $this->Form->control('billing_postcode', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-md-6">
                                <label class="admin-form-label" style="font-size: 13px;">Country</label>
                                <?= $this->Form->control('billing_country', ['label' => false, 'class' => 'admin-form-input']) ?>
                            </div>
                            <div class="col-12 mt-3">
                                <label style="display: flex; align-items: center; gap: 8px; font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary); cursor: pointer;">
                                    <?= $this->Form->checkbox('is_default', ['hiddenField' => true, 'style' => 'accent-color: var(--admin-brand-icon);']) ?>
                                    <span>Set as default payment details</span>
                                </label>
                            </div>
                            <div class="col-12 mt-4 pt-4" style="border-top: 1px solid var(--admin-card-border);">
                                <div class="d-flex gap-3">
                                    <?= $this->Form->button($editingExistingProfile ? 'Update Details' : 'Save Details', ['class' => 'z-billing-btn-primary', 'style' => 'border: none; cursor: pointer;']) ?>
                                    <?php if ($editingExistingProfile): ?>
                                        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary" style="text-decoration: none; border-radius: 24px; padding: 10px 20px;">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.z-billing-tab');
    const sections = document.querySelectorAll('.z-billing-section');

    function switchTab(tabId) {
        // Update tabs
        tabs.forEach(t => t.classList.remove('active'));
        const activeTab = document.querySelector(`.z-billing-tab[data-tab="${tabId}"]`);
        if (activeTab) activeTab.classList.add('active');

        // Update sections
        sections.forEach(s => {
            s.style.display = 'none';
            s.classList.remove('active-section');
        });
        const activeSection = document.getElementById(tabId);
        if (activeSection) {
            activeSection.style.display = 'block';
            activeSection.classList.add('active-section');
        }
        
        // Update URL hash without jumping
        history.replaceState(null, null, '#' + tabId);
    }

    // Handle tab clicks
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });

    // Check initial hash
    const hash = window.location.hash.replace('#', '');
    if (hash && ['overview', 'history', 'details'].includes(hash)) {
        switchTab(hash);
    } else {
        switchTab('overview');
    }
});
</script>

