<?php
declare(strict_types=1);

namespace App\Exception\Payments;

use RuntimeException;
use Throwable;

abstract class PaymentWebhookException extends RuntimeException
{
    protected array $context;

    /**
     * Construct.
     *
     * @param mixed $message Message.
     * @param mixed $context Context.
     * @param mixed $code Code.
     * @param mixed $previous Previous.
     * @return mixed
     */
    public function __construct(
        string $message,
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get context.
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
