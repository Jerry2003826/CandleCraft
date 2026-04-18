<?php
declare(strict_types=1);

namespace App\Exception\Payments;

use RuntimeException;
use Throwable;

abstract class PaymentWebhookException extends RuntimeException
{
    protected array $context;

    public function __construct(
        string $message,
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
