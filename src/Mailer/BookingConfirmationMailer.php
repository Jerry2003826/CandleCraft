<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class BookingConfirmationMailer extends Mailer
{
    /**
     * Booking confirmation.
     *
     * @param mixed $payload Payload.
     */
    public function bookingConfirmation(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject('Your CandleCraft Academy booking has been received')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('booking_confirmation');

        return $this;
    }
}
