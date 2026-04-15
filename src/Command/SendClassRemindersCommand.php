<?php
declare(strict_types=1);

namespace App\Command;

use App\Mailer\ClassReminderMailer;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class SendClassRemindersCommand extends Command
{
    public static function defaultName(): string
    {
        return 'send_class_reminders';
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $bookingsTable = TableRegistry::getTableLocator()->get('Bookings');
        $notificationsTable = TableRegistry::getTableLocator()->get('Notifications');

        $now = DateTime::now();
        $windowStart = $now->addHours(24);
        $windowEnd = $now->addHours(25);

        $bookings = $bookingsTable->find()
            ->contain([
                'Students' => ['Users'],
                'Classes' => ['Courses', 'Teachers'],
            ])
            ->where([
                'Bookings.booking_status IN' => ['confirmed', 'completed'],
                'Bookings.reminder_sent_at IS' => null,
                'Classes.start_datetime >=' => $windowStart,
                'Classes.start_datetime <' => $windowEnd,
            ])
            ->all();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($bookings as $booking) {
            $recipientEmail = (string)($booking->student?->user?->email ?? '');
            $recipientName = (string)($booking->student?->student_name ?? '');
            $className = (string)($booking->class_entity?->course?->course_name ?? $booking->class_entity?->class_code ?? 'Class');
            $schedule = $booking->class_entity?->start_datetime?->format('D j M Y, g:ia') ?? 'TBA';
            $location = (string)($booking->class_entity?->location ?? 'TBA');
            $teacherName = (string)($booking->class_entity?->teacher?->teacher_name ?? 'Your teacher');

            if ($recipientEmail === '') {
                $failedCount++;
                Log::warning(sprintf('Reminder skipped for booking %d: missing recipient email.', (int)$booking->booking_id));
                continue;
            }

            try {
                $mailer = new ClassReminderMailer('default');
                $mailer->send('classReminder', [[
                    'recipient_name' => $recipientName,
                    'recipient_email' => $recipientEmail,
                    'class_name' => $className,
                    'schedule' => $schedule,
                    'location' => $location,
                    'teacher_name' => $teacherName,
                ]]);

                if ($booking->student?->user_id) {
                    $notification = $notificationsTable->newEntity([
                        'user_id' => $booking->student->user_id,
                        'title' => 'Class Reminder',
                        'message' => sprintf('Reminder: %s starts tomorrow at %s.', $className, $schedule),
                        'notification_type' => 'class_reminder',
                        'is_read' => false,
                    ]);
                    $notificationsTable->save($notification);
                }

                $booking->reminder_sent_at = $now;
                $bookingsTable->save($booking);
                $sentCount++;
            } catch (\Throwable $exception) {
                $failedCount++;
                Log::error(sprintf(
                    'Failed to send class reminder for booking %d: %s',
                    (int)$booking->booking_id,
                    $exception->getMessage()
                ));
            }
        }

        $io->out(sprintf('Class reminders sent: %d, failed: %d', $sentCount, $failedCount));

        return static::CODE_SUCCESS;
    }
}
