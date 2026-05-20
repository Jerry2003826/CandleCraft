<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Log\Log;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\Locator\LocatorInterface;
use Throwable;

class PaymentAdminAlertService
{
    use MailerAwareTrait;

    private object $adminsTable;
    private object $notificationsTable;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @return mixed
     */
    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->adminsTable = $locator->get('Admins');
        $this->notificationsTable = $locator->get('Notifications');
    }

    /**
     * Alert.
     *
     * @param mixed $title Title.
     * @param mixed $message Message.
     * @param mixed $context Context.
     */
    public function alert(string $title, string $message, array $context = []): void
    {
        try {
            $admins = $this->adminsTable->find()
                ->contain(['Users'])
                ->matching('Users', function ($query) {
                    return $query->where([
                        'Users.user_role' => 'admin',
                        'Users.account_status' => 'active',
                    ]);
                })
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Unable to load admins for payment alert: ' . $exception->getMessage());

            return;
        }

        foreach ($admins as $admin) {
            $user = $admin->user ?? null;
            $userId = (int)($user?->user_id ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $this->createNotification($userId, $title, $message);
            $this->sendEmail((string)($user->email ?? ''), (string)($admin->admin_name ?? 'Admin'), $title, $message, $context);
        }
    }

    /**
     * Create notification.
     *
     * @param mixed $userId Userid.
     * @param mixed $title Title.
     * @param mixed $message Message.
     */
    private function createNotification(int $userId, string $title, string $message): void
    {
        try {
            $notification = $this->notificationsTable->newEntity([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'notification_type' => 'payment_alert',
                'is_read' => false,
            ]);
            $this->notificationsTable->saveOrFail($notification);
        } catch (Throwable $exception) {
            Log::warning('Unable to create admin payment notification: ' . $exception->getMessage());
        }
    }

    /**
     * Send email.
     *
     * @param mixed $email Email.
     * @param mixed $name Name.
     * @param mixed $title Title.
     * @param mixed $message Message.
     * @param mixed $context Context.
     */
    private function sendEmail(string $email, string $name, string $title, string $message, array $context): void
    {
        if ($email === '' || Configure::read('Payments.admin_alerts.email_enabled') === false) {
            return;
        }

        try {
            /** @var \App\Mailer\PaymentAlertMailer $mailer */
            $mailer = $this->getMailer('PaymentAlert');
            $mailer->paymentAlert([
                'recipient_email' => $email,
                'recipient_name' => $name,
                'title' => $title,
                'message' => $message,
                'context' => $context,
            ])->deliver();
        } catch (Throwable $exception) {
            Log::warning('Unable to send admin payment alert email: ' . $exception->getMessage());
        }
    }
}
