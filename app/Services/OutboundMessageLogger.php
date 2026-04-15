<?php

namespace App\Services;

use App\Models\ContactPoint;
use App\Models\Message;
use App\Models\ReminderSchedule;
use App\Models\User;
use Carbon\CarbonInterface;

class OutboundMessageLogger
{
    public function log(
        ?User $user,
        ?ContactPoint $contactPoint,
        string $channel,
        string $provider,
        string $to,
        string $subject,
        string $body,
        array $metadata = [],
        ?string $externalId = null,
        ?string $inReplyTo = null,
    ): Message {
        $message = Message::query()->create([
            'user_id' => $user?->id,
            'contact_point_id' => $contactPoint?->id,
            'direction' => 'outbound',
            'channel' => $channel,
            'provider' => $provider,
            'external_id' => $externalId,
            'in_reply_to' => $inReplyTo,
            'subject' => $subject,
            'body_text' => $body,
            'parsed_status' => 'sent',
            'sent_at' => now(),
            'processed_at' => now(),
            'metadata' => $metadata,
        ]);

        if ($contactPoint !== null) {
            $contactPoint->update([
                'last_outbound_at' => $message->sent_at,
            ]);
        }

        return $message;
    }

    public function logReminderSendFailure(
        User $user,
        ?ContactPoint $contactPoint,
        ReminderSchedule $schedule,
        string $reason,
        CarbonInterface $nextRunAt,
    ): Message {
        $body = implode("\n", [
            'Reminder email was not sent.',
            'Reason: '.$reason,
            'User ID: '.$user->id,
            'Schedule ID: '.$schedule->id,
            'Next run (UTC): '.$nextRunAt->copy()->utc()->toIso8601String(),
        ]);

        return Message::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contactPoint?->id,
            'direction' => 'outbound',
            'channel' => 'email',
            'provider' => 'smtp',
            'subject' => 'Weightsy reminder not sent',
            'body_text' => $body,
            'parsed_status' => 'skipped',
            'sent_at' => null,
            'processed_at' => now(),
            'metadata' => [
                'category' => 'reminder_failed',
                'reminder_schedule_id' => $schedule->id,
                'failure_reason' => $reason,
                'next_run_at' => $nextRunAt->toIso8601String(),
            ],
        ]);
    }
}
