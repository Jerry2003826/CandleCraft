<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Stripe\Stripe;

final class StripeApiBootstrap
{
    /**
     * Configure.
     *
     * @param mixed $secretKey Secretkey.
     */
    public static function configure(?string $secretKey = null): void
    {
        Stripe::setApiKey($secretKey ?? (string)Configure::read('Stripe.secret_key'));

        if (method_exists(Stripe::class, 'setApiVersion')) {
            Stripe::setApiVersion(StripeConfiguration::apiVersion());
        }

        if (method_exists(Stripe::class, 'setMaxNetworkRetries')) {
            Stripe::setMaxNetworkRetries(2);
        }
    }
}
