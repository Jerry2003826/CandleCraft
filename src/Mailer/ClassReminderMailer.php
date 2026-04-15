<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class ClassReminderMailer extends Mailer
{
    public function classReminder(array $payload): static
    {
        $this
            ->setTo($payload['recipient_email'], $payload['recipient_name'] ?: null)
            ->setSubject('Reminder: your CandleCraft Academy class is tomorrow')
            ->setEmailFormat('both')
            ->setViewVars($payload);

        $this->viewBuilder()->setTemplate('class_reminder');

        return $this;
    }
}
