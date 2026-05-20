<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class PaymentRefundMailer extends Mailer
{
    /**
     * Request received.
     *
     * @param mixed $payload Payload.
     */
    public function requestReceived(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject('Your CandleCraft Academy refund request has been received')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('refund_request_received');

        return $this;
    }

    /**
     * Refund processed.
     *
     * @param mixed $payload Payload.
     */
    public function refundProcessed(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject('Your CandleCraft Academy refund has been processed')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('refund_processed');

        return $this;
    }
}
