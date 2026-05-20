<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Stripe\Refund;

class StripeRefundGateway implements StripeRefundGatewayInterface
{
    /**
     * Construct.
     *
     * @param mixed $secretKey Secretkey.
     * @return mixed
     */
    public function __construct(
        private readonly ?string $secretKey = null,
    ) {
    }

    /**
     * Create refund.
     *
     * @param mixed $payload Payload.
     */
    public function createRefund(array $payload): object
    {
        StripeApiBootstrap::configure($this->secretKey ?? (string)Configure::read('Stripe.secret_key'));

        return Refund::create($payload);
    }
}
