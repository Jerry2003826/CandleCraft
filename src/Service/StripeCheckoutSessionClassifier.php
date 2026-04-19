<?php
declare(strict_types=1);

namespace App\Service;

final class StripeCheckoutSessionClassifier
{
    public const STATE_PAID = 'paid';
    public const STATE_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATE_OPEN_NON_PAID = 'open_non_paid';
    public const STATE_OPEN_UNPAID = self::STATE_OPEN_NON_PAID;
    public const STATE_EXPIRED = 'expired';
    public const STATE_STALE = 'stale';

    public function classify(object $session): array
    {
        $paymentStatus = strtolower(trim((string)($session->payment_status ?? '')));
        $sessionStatus = strtolower(trim((string)($session->status ?? '')));

        if ($paymentStatus === 'paid') {
            return $this->result(self::STATE_PAID, $sessionStatus, $paymentStatus);
        }

        if ($sessionStatus === 'complete' && $paymentStatus !== 'paid') {
            return $this->result(self::STATE_AWAITING_PAYMENT, $sessionStatus, $paymentStatus);
        }

        if ($sessionStatus === 'open' && in_array($paymentStatus, ['unpaid', 'no_payment_required'], true)) {
            return $this->result(self::STATE_OPEN_NON_PAID, $sessionStatus, $paymentStatus);
        }

        if ($sessionStatus === 'open') {
            return $this->result(self::STATE_STALE, $sessionStatus, $paymentStatus);
        }

        if ($sessionStatus === 'expired') {
            return $this->result(self::STATE_EXPIRED, $sessionStatus, $paymentStatus);
        }

        return $this->result(self::STATE_STALE, $sessionStatus, $paymentStatus);
    }

    private function result(string $state, string $sessionStatus, string $paymentStatus): array
    {
        return [
            'state' => $state,
            'session_status' => $sessionStatus,
            'payment_status' => $paymentStatus,
        ];
    }
}
