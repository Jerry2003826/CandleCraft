<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class BookingPaymentMailer extends Mailer
{
    /**
     * Payment receipt.
     *
     * @param mixed $payload Payload.
     */
    public function paymentReceipt(array $payload): static
    {
        if (!empty($payload['receipt_snapshot_svg']) && !empty($payload['receipt_snapshot_content_id'])) {
            $this->addAttachments([
                ($payload['receipt_snapshot_filename'] ?? 'candlecraft-receipt.svg') => [
                    'data' => (string)$payload['receipt_snapshot_svg'],
                    'mimetype' => 'image/svg+xml',
                    'contentId' => (string)$payload['receipt_snapshot_content_id'],
                ],
            ]);
        }

        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject('Your CandleCraft Academy booking is confirmed')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('booking_payment_receipt');

        return $this;
    }
}
