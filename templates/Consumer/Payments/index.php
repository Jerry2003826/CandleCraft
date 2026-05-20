<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $bookings
 */
$this->assign('title', 'Payment Portal');

$bookingList = is_object($bookings) && method_exists($bookings, 'toList') ? $bookings->toList() : (array)$bookings;
$identity = $this->request->getAttribute('identity');
$billingName = $identity ? (string)($identity->get('username') ?: $identity->get('email')) : '';
$billingEmail = $identity ? (string)$identity->get('email') : '';

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
            <button type="button" class="z-billing-tab active" data-tab="overview" aria-pressed="true" aria-controls="overview">Overview</button>
            <button type="button" class="z-billing-tab" data-tab="history" aria-pressed="false" aria-controls="history">Payment History</button>
            <button type="button" class="z-billing-tab" data-tab="details" aria-pressed="false" aria-controls="details">Billing Details</button>
        </div>
    </div>

    <div id="overview" class="z-billing-section active-section">
        <div class="z-billing-balance-card mb-4">
            <div class="z-billing-balance-main">
                <div class="z-billing-amount">$ <?= number_format($pendingAmount, 2) ?></div>
                <div class="z-billing-balance-divider"></div>
                <div class="z-billing-balance-details">
                    <div class="z-billing-balance-item">
                        <span class="z-billing-balance-label">Payment pending</span>
                        <span class="z-billing-balance-value"><?= $pendingCount ?> bookings</span>
                    </div>
                    <div class="z-billing-balance-item">
                        <span class="z-billing-balance-label">Paid receipts</span>
                        <span class="z-billing-balance-value"><?= $paidCount ?></span>
                    </div>
                </div>
            </div>
            <div class="z-billing-balance-actions">
                <button type="button" class="z-billing-btn-primary" data-tab-trigger="history">View payments</button>
                <button type="button" data-tab-trigger="history" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); text-decoration: none; margin-left: 16px; border: 0; background: transparent; padding: 0;">
                    View History
                </button>
                <button type="button" data-tab-trigger="details" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); text-decoration: none; margin-left: 16px; border: 0; background: transparent; padding: 0;">
                    Edit billing details
                </button>
            </div>
        </div>

    </div>

    <div id="history" class="z-billing-section" hidden>
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
                                $latestPaymentStatus = 'pending';
                                $hasPaidRecord = false;
                                $refundablePayment = null;
                                foreach ($booking->payments ?? [] as $payment) {
                                    $latestPayment = $payment;
                                    $latestPaymentStatus = (string)$payment->payment_status;
                                    if ($payment->payment_status === 'paid') {
                                        $hasPaidRecord = true;
                                    }
                                    if (
                                        in_array((string)$payment->payment_status, ['paid', 'partially_refunded'], true)
                                        && round((float)$payment->refunded_amount, 2) < round((float)$payment->amount, 2)
                                    ) {
                                        $refundablePayment = $payment;
                                    }
                                }

                                $isPaid = in_array($booking->booking_status, ['confirmed', 'completed'], true) && $hasPaidRecord;
                                if ($latestPaymentStatus === 'refund_required') {
                                    $paymentBadgeClass = 'admin-badge-warning';
                                    $paymentBadgeLabel = 'Refund Required';
                                } elseif ($isPaid) {
                                    $paymentBadgeClass = 'admin-badge-success';
                                    $paymentBadgeLabel = 'Payment Paid';
                                } else {
                                    [$paymentBadgeClass, $paymentBadgeLabel] = match ($latestPaymentStatus) {
                                        'failed' => ['admin-badge-danger', 'Payment Failed'],
                                        'expired', 'voided' => ['admin-badge-neutral', 'Payment Cancelled'],
                                        'refund_required' => ['admin-badge-warning', 'Refund Required'],
                                        'refunded' => ['admin-badge-neutral', 'Refunded'],
                                        'partially_refunded' => ['admin-badge-info', 'Partially Refunded'],
                                        'disputed' => ['admin-badge-danger', 'Disputed'],
                                        default => ['admin-badge-warning', 'Payment Pending'],
                                    };
                                }
                                $canPay = !$isPaid
                                    && in_array($booking->booking_status, ['pending', 'confirmed'], true)
                                    && !in_array($latestPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded', 'disputed'], true);
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
                                    <?php if ($canPay): ?>
                                        <a href="<?= $this->Url->build(['action' => 'process', $booking->booking_id]) ?>" class="z-billing-btn-primary" style="padding: 8px 16px; font-size: 13px;">Pay Now</a>
                                    <?php elseif ($refundablePayment): ?>
                                        <div class="d-flex gap-2 justify-content-end align-items-center">
                                            <a href="<?= $this->Url->build(['action' => 'receipt', $refundablePayment->payment_id]) ?>" class="admin-action-link view" style="text-decoration: none; font-size: 13px;">View Receipt</a>
                                            <?= $this->Form->postLink('Request Refund', ['action' => 'requestRefund', $refundablePayment->payment_id], [
                                                'class' => 'admin-action-link admin-action-link--delete',
                                                'style' => 'text-decoration: none; font-size: 13px;',
                                                'confirm' => 'Submit this payment for admin refund review?',
                                            ]) ?>
                                        </div>
                                    <?php elseif ($latestPaymentStatus === 'refund_required'): ?>
                                        <span style="color: var(--admin-text-secondary); font-size: 13px;">Refund requested</span>
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

    <div id="details" class="z-billing-section" hidden>
        <h2 class="z-billing-section-title">Billing Details</h2>
        <p class="text-muted" style="font-size:13px; margin-bottom:16px;">
            We use these details on receipts and refund correspondence. Update your account
            email or display name from <a href="<?= $this->Url->build(['controller' => 'Account', 'action' => 'edit']) ?>">My Account</a>.
        </p>
        <div class="admin-form-card" style="border-radius:12px; padding:24px; max-width:520px;">
            <div class="mb-3">
                <label for="billing-name" class="admin-form-label">Billing name</label>
                <input
                    type="text"
                    id="billing-name"
                    name="billing_name"
                    value="<?= h($billingName) ?>"
                    class="admin-form-input"
                    autocomplete="name"
                    readonly
                >
            </div>
            <div class="mb-3">
                <label for="billing-email" class="admin-form-label">Billing email</label>
                <input
                    type="email"
                    id="billing-email"
                    name="billing_email"
                    value="<?= h($billingEmail) ?>"
                    class="admin-form-input"
                    autocomplete="email"
                    readonly
                >
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.z-billing-tab');
    const sections = document.querySelectorAll('.z-billing-section');
    const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
    const availableTabs = ['overview', 'history', 'details'];

    function switchTab(tabId) {
        // Update tabs
        tabs.forEach(t => {
            t.classList.remove('active');
            t.setAttribute('aria-pressed', 'false');
        });
        const activeTab = document.querySelector(`.z-billing-tab[data-tab="${tabId}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
            activeTab.setAttribute('aria-pressed', 'true');
        }

        // Update sections
        sections.forEach(s => {
            s.hidden = true;
            s.classList.remove('active-section');
        });
        const activeSection = document.getElementById(tabId);
        if (activeSection) {
            activeSection.hidden = false;
            activeSection.classList.add('active-section');
        }
        
        // Update URL hash without jumping
        history.replaceState(null, null, '#' + tabId);
    }

    // Handle tab clicks
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });

    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab-trigger');
            switchTab(tabId);
            const targetTab = document.querySelector(`.z-billing-tab[data-tab="${tabId}"]`);
            if (targetTab) {
                targetTab.focus();
            }
        });
    });

    // Check initial hash
    const hash = window.location.hash.replace('#', '');
    if (hash && availableTabs.includes(hash)) {
        switchTab(hash);
    } else {
        switchTab('overview');
    }
});
</script>
