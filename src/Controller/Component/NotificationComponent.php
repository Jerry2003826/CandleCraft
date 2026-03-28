<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class NotificationComponent extends Component
{
    protected array $_defaultConfig = [];

    public function send(int $userId, string $title, string $message, string $type = 'system'): bool
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Notifications');
        $notification = $notificationsTable->newEntity([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'notification_type' => $type,
            'is_read' => false,
        ]);

        return (bool)$notificationsTable->save($notification);
    }

    public function sendBookingConfirmation(int $userId, string $className, string $schedule): void
    {
        $this->send(
            $userId,
            'Booking Confirmed',
            "Your booking for \"{$className}\" on {$schedule} has been confirmed.",
            'booking_confirmation',
        );
    }

    public function sendClassReminder(int $userId, string $className, string $schedule): void
    {
        $this->send(
            $userId,
            'Class Reminder',
            "Reminder: You have \"{$className}\" tomorrow at {$schedule}.",
            'class_reminder',
        );
    }

    public function sendPaymentReceipt(int $userId, string $className, float $amount): void
    {
        $this->send(
            $userId,
            'Payment Received',
            "Payment of $" . number_format($amount, 2) . " for \"{$className}\" has been received.",
            'payment_receipt',
        );
    }

    public function getUnreadCount(int $userId): int
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Notifications');

        return $notificationsTable->find()
            ->where(['Notifications.user_id' => $userId, 'Notifications.is_read' => false])
            ->count();
    }
}
