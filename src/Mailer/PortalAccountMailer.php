<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class PortalAccountMailer extends Mailer
{
    public function portalCredentials(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'])
            ->setSubject('Your CandleCraft Academy portal account is ready')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('portal_account_credentials');

        return $this;
    }
}
