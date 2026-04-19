<?php
declare(strict_types=1);

namespace App\Service;

interface PaymentConfirmationServiceInterface
{
    public function confirmCheckoutSession(
        object $session,
        string $eventType = 'checkout.session.completed',
        string $confirmationSource = 'stripe_webhook',
    ): string;

    public function markCheckoutSessionFailed(object $session): string;

    public function markCheckoutSessionExpired(object $session): string;
}
