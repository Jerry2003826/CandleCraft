<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class PaymentAlertMailer extends Mailer
{
    /**
     * Payment alert.
     *
     * @param mixed $payload Payload.
     */
    public function paymentAlert(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject('[CandleCraft] ' . $payload['title'])
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('payment_alert');

        return $this;
    }
}
