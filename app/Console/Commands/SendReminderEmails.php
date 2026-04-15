<?php

namespace App\Console\Commands;

use App\Models\ReminderSchedule;
use App\Services\OutboundMessageLogger;
use App\Services\ReminderMailer;
use App\Services\ReminderScheduleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendReminderEmails extends Command
{
    protected $signature = 'weightsy:reminders:send {--limit=100 : Maximum reminders to send}';

    protected $description = 'Send recurring reminder emails to confirmed Weightsy users';

    public function handle(
        ReminderMailer $reminderMailer,
        ReminderScheduleManager $scheduleManager,
        OutboundMessageLogger $messageLogger,
    ): int {
        $limit = (int) $this->option('limit');
        $sent = 0;

        $ids = ReminderSchedule::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            $email = DB::transaction(function () use ($id, $reminderMailer, $scheduleManager, $messageLogger) {
                $schedule = ReminderSchedule::query()
                    ->with(['user', 'contactPoint'])
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if ($schedule === null || $schedule->next_run_at === null || $schedule->next_run_at->isFuture()) {
                    return null;
                }

                $user = $schedule->user;

                if (! $user || $user->notification_confirmed_at === null || $user->unsubscribed_at !== null) {
                    $schedule->update([
                        'next_run_at' => $scheduleManager->nextRunAt($schedule->timezone, $schedule->remind_at_local),
                    ]);

                    return null;
                }

                if (! $schedule->contactPoint || ! $schedule->contactPoint->receives_reminders) {
                    $schedule->update([
                        'next_run_at' => $scheduleManager->nextRunAt($schedule->timezone, $schedule->remind_at_local),
                    ]);

                    return null;
                }

                $result = $reminderMailer->send($user);

                if (! $result['sent']) {
                    $reason = (string) ($result['reason'] ?? 'unknown');
                    $reasonShort = mb_substr($reason, 0, 191);
                    $nextRun = $scheduleManager->nextRunAt($schedule->timezone, $schedule->remind_at_local);

                    $schedule->update([
                        'next_run_at' => $nextRun,
                        'last_reminder_failure_at' => now(),
                        'last_reminder_failure_reason' => $reasonShort,
                        'reminder_failure_count' => $schedule->reminder_failure_count + 1,
                    ]);

                    $schedule->refresh();

                    Log::channel('reminders')->warning('Reminder send skipped; next run deferred', [
                        'schedule_id' => $schedule->id,
                        'user_id' => $user->id,
                        'reason' => $reason,
                        'next_run_at' => $schedule->next_run_at?->toIso8601String(),
                    ]);

                    $messageLogger->logReminderSendFailure(
                        $user,
                        $schedule->contactPoint,
                        $schedule,
                        $reason,
                        $nextRun,
                    );

                    return null;
                }

                $schedule->update([
                    'last_sent_for_date' => now($schedule->timezone)->toDateString(),
                    'next_run_at' => $scheduleManager->nextRunAt($schedule->timezone, $schedule->remind_at_local),
                    'last_reminder_failure_at' => null,
                    'last_reminder_failure_reason' => null,
                    'reminder_failure_count' => 0,
                ]);

                return $user->email;
            });

            if ($email !== null) {
                $sent++;
                $this->line('Sent reminder to '.$email);
            }
        }

        $this->info("Sent {$sent} reminder email(s).");

        return self::SUCCESS;
    }
}
