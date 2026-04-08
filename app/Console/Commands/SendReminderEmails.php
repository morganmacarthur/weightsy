<?php

namespace App\Console\Commands;

use App\Models\ReminderSchedule;
use App\Services\ReminderMailer;
use App\Services\ReminderScheduleManager;
use Illuminate\Console\Command;

class SendReminderEmails extends Command
{
    protected $signature = 'weightsy:reminders:send {--limit=100 : Maximum reminders to send}';

    protected $description = 'Send recurring reminder emails to confirmed Weightsy users';

    public function handle(ReminderMailer $reminderMailer, ReminderScheduleManager $scheduleManager): int
    {
        $limit = (int) $this->option('limit');
        $sent = 0;

        $schedules = ReminderSchedule::query()
            ->with(['user', 'contactPoint'])
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->limit($limit)
            ->get();

        foreach ($schedules as $schedule) {
            $user = $schedule->user;

            if (! $user || $user->notification_confirmed_at === null || $user->unsubscribed_at !== null) {
                continue;
            }

            if (! $schedule->contactPoint || ! $schedule->contactPoint->receives_reminders) {
                continue;
            }

            $reminderMailer->send($user);

            $schedule->update([
                'last_sent_for_date' => now($schedule->timezone)->toDateString(),
                'next_run_at' => $scheduleManager->nextRunAt($schedule->timezone, $schedule->remind_at_local),
            ]);

            $sent++;
            $this->line('Sent reminder to '.$user->email);
        }

        $this->info("Sent {$sent} reminder email(s).");

        return self::SUCCESS;
    }
}
