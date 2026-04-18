<?php
declare(strict_types=1);

namespace App\Service;

interface PaymentConfirmationServiceInterface
{
    public function confirmCheckoutSession(object $session): string;
}
