<?php
declare(strict_types=1);

namespace App\Service;

interface PaymentConfirmationServiceInterface
{
    /**
     * Confirm checkout session.
     *
     * @param mixed $session Session.
     * @param mixed $eventType Eventtype.
     * @param mixed $confirmationSource Confirmationsource.
     */
    public function confirmCheckoutSession(
        object $session,
        string $eventType = 'checkout.session.completed',
        string $confirmationSource = 'stripe_webhook',
    ): string;

    /**
     * Mark checkout session failed.
     *
     * @param mixed $session Session.
     */
    public function markCheckoutSessionFailed(object $session): string;

    /**
     * Mark checkout session expired.
     *
     * @param mixed $session Session.
     */
    public function markCheckoutSessionExpired(object $session): string;
}
