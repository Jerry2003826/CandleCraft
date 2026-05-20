<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class PasswordResetMailer extends Mailer
{
    /**
     * Reset link.
     *
     * @param mixed $payload Payload.
     */
    public function resetLink(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'])
            ->setSubject('Reset your CandleCraft Academy password')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('password_reset');

        return $this;
    }
}
