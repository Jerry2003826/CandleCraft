<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class EnquiryReplyMailer extends Mailer
{
    public function enquiryReply(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject($payload['subject'])
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('enquiry_reply');

        return $this;
    }
}
