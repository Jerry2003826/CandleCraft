<?php
declare(strict_types=1);

namespace App\Command;

use App\Mailer\ClassReminderMailer;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Throwable;

/**
 * SendClassRemindersCommand
 *
 * Sends a reminder email + in-app notification for every booking whose class
 * starts inside a configurable window in the future. Designed to be wired up
 * to cron / launchd / Windows Task Scheduler so customers get a 24h heads-up
 * automatically, without anyone having to push a button.
 *
 * Examples:
 *
 *   bin/cake send_class_reminders                      # default 24-25h window
 *   bin/cake send_class_reminders --window-hours 24    # any custom lead time
 *   bin/cake send_class_reminders --dry-run            # report what *would* go
 *   bin/cake send_class_reminders --window-hours 2 --window-span 60
 *                                                       # 2-hour reminder, 60min span
 */
class SendClassRemindersCommand extends Command
{
    /**
     * Default name.
     */
    public static function defaultName(): string
    {
        return 'send_class_reminders';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription(
                'Send 24-hour class reminder emails (and matching in-app notifications) for every '
                . 'confirmed/completed booking whose class starts inside the configured window. '
                . 'Schedule this via cron / launchd / Task Scheduler at least once per hour.',
            )
            ->addOption('window-hours', [
                'short' => 'w',
                'help' => 'Lead time in hours before class start. Defaults to 24.',
                'default' => '24',
            ])
            ->addOption('window-span', [
                'short' => 's',
                'help' => 'Width of the reminder window in minutes. Should be >= cron interval. Defaults to 60.',
                'default' => '60',
            ])
            ->addOption('dry-run', [
                'help' => 'Print which bookings would receive a reminder without sending or persisting anything.',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('limit', [
                'help' => 'Hard cap on how many reminders to dispatch in a single run.',
                'default' => '500',
            ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $windowHours = max(0, (int)$args->getOption('window-hours'));
        $windowSpan = max(1, (int)$args->getOption('window-span'));
        $dryRun = (bool)$args->getOption('dry-run');
        $limit = max(1, (int)$args->getOption('limit'));

        $bookingsTable = TableRegistry::getTableLocator()->get('Bookings');
        $notificationsTable = TableRegistry::getTableLocator()->get('Notifications');

        $now = DateTime::now();
        $windowStart = $now->addHours($windowHours);
        $windowEnd = $windowStart->addMinutes($windowSpan);

        $io->info(sprintf(
            'Scanning bookings starting between %s and %s (lead-time %dh, span %dm)%s',
            $windowStart->format('Y-m-d H:i'),
            $windowEnd->format('Y-m-d H:i'),
            $windowHours,
            $windowSpan,
            $dryRun ? ' [DRY RUN]' : '',
        ));

        $bookings = $bookingsTable->find()
            ->contain([
                'Students' => ['Users'],
                'Parents' => ['Users'],
                'Classes' => ['Courses', 'Teachers'],
            ])
            ->where([
                'Bookings.booking_status IN' => ['confirmed', 'completed'],
                'Bookings.reminder_sent_at IS' => null,
                'Classes.start_datetime >=' => $windowStart,
                'Classes.start_datetime <' => $windowEnd,
            ])
            ->limit($limit)
            ->all();

        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($bookings as $booking) {
            $recipient = $this->resolveRecipient($booking);
            $className = (string)(
                $booking->class_entity?->course?->course_name
                ?? $booking->class_entity?->class_code
                ?? 'Class'
            );
            $schedule = $booking->class_entity?->start_datetime?->format('D j M Y, g:ia') ?? 'TBA';
            $location = (string)($booking->class_entity?->location ?? 'TBA');
            $teacherName = (string)($booking->class_entity?->teacher?->teacher_name ?? 'Your teacher');

            if ($recipient['email'] === '') {
                $skippedCount++;
                $io->warning(sprintf(
                    'Booking #%d skipped: missing recipient email (student=%s, class=%s).',
                    (int)$booking->booking_id,
                    (string)($booking->student?->student_name ?? '?'),
                    $className,
                ));
                Log::warning(sprintf(
                    'Reminder skipped for booking %d: missing recipient email.',
                    (int)$booking->booking_id,
                ));
                continue;
            }

            $io->out(sprintf(
                ' - Booking #%d -> <info>%s</info> (%s @ %s)',
                (int)$booking->booking_id,
                $recipient['email'],
                $className,
                $schedule,
            ));

            if ($dryRun) {
                $sentCount++;
                continue;
            }

            try {
                $mailer = new ClassReminderMailer('default');
                $mailer->send('classReminder', [[
                    'recipient_name' => $recipient['name'],
                    'recipient_email' => $recipient['email'],
                    'class_name' => $className,
                    'schedule' => $schedule,
                    'location' => $location,
                    'teacher_name' => $teacherName,
                    'login_url' => Router::url('/login', true),
                ]]);

                if ($recipient['user_id'] !== null) {
                    $notification = $notificationsTable->newEntity([
                        'user_id' => $recipient['user_id'],
                        'title' => 'Class Reminder',
                        'message' => sprintf('Reminder: %s starts at %s.', $className, $schedule),
                        'notification_type' => 'class_reminder',
                        'is_read' => false,
                    ]);
                    $notificationsTable->save($notification);
                }

                $booking->reminder_sent_at = $now;
                $bookingsTable->save($booking);
                $sentCount++;
            } catch (Throwable $exception) {
                $failedCount++;
                $io->error(sprintf(
                    'Booking #%d FAILED: %s',
                    (int)$booking->booking_id,
                    $exception->getMessage(),
                ));
                Log::error(sprintf(
                    'Failed to send class reminder for booking %d: %s',
                    (int)$booking->booking_id,
                    $exception->getMessage(),
                ));
            }
        }

        $io->hr();
        $io->success(sprintf(
            'Class reminders %s - sent: %d, skipped: %d, failed: %d',
            $dryRun ? 'DRY RUN' : 'dispatched',
            $sentCount,
            $skippedCount,
            $failedCount,
        ));

        return $failedCount > 0 ? static::CODE_ERROR : static::CODE_SUCCESS;
    }

    /**
     * @return array{email: string, name: string, user_id: int|null}
     */
    private function resolveRecipient(object $booking): array
    {
        $parentUser = $booking->parent?->user ?? null;
        if ($parentUser && filter_var((string)($parentUser->email ?? ''), FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => (string)$parentUser->email,
                'name' => (string)($booking->parent?->parent_name ?? $parentUser->username ?? ''),
                'user_id' => isset($parentUser->user_id) ? (int)$parentUser->user_id : null,
            ];
        }

        $student = $booking->student ?? null;
        $studentUser = $student?->user ?? null;
        if ($studentUser && filter_var((string)($studentUser->email ?? ''), FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => (string)$studentUser->email,
                'name' => (string)($student?->student_name ?? $studentUser->username ?? ''),
                'user_id' => isset($studentUser->user_id) ? (int)$studentUser->user_id : null,
            ];
        }

        return [
            'email' => '',
            'name' => '',
            'user_id' => null,
        ];
    }
}
