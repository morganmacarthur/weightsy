<?php

namespace App\Services;

use App\DataTransferObjects\InboundMessageData;
use App\DataTransferObjects\InboundProcessingResult;
use App\Models\Checkin;
use App\Models\ContactPoint;
use App\Models\Message;
use App\Models\ReminderSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboundCheckinProcessor
{
    public function __construct(
        private readonly CheckinMessageParser $parser,
        private readonly ContactChannelGuesser $channelGuesser,
        private readonly ReminderScheduleManager $scheduleManager,
    ) {
    }

    public function process(InboundMessageData $inbound): InboundProcessingResult
    {
        $body = trim($inbound->text);
        $parsed = $this->parser->parse($body);
        $receivedAt = $inbound->receivedAt ?? CarbonImmutable::now();
        $channel = $inbound->channel ?: $this->channelGuesser->guess($inbound->from);
        $normalizedAddress = Str::lower(trim($inbound->from));

        return DB::transaction(function () use ($body, $parsed, $receivedAt, $channel, $normalizedAddress, $inbound) {
            if ($parsed === null) {
                $message = Message::query()->create([
                    'direction' => 'inbound',
                    'channel' => $channel,
                    'provider' => $inbound->provider,
                    'external_id' => $inbound->externalId,
                    'subject' => $inbound->subject,
                    'body_text' => $body,
                    'parsed_status' => 'unrecognized',
                    'received_at' => $receivedAt,
                    'processed_at' => now(),
                    'metadata' => $inbound->metadata,
                ]);

                return new InboundProcessingResult(
                    recognized: false,
                    createdUser: false,
                    user: null,
                    checkin: null,
                    message: $message,
                    parsedCheckin: null,
                );
            }

            $contactPoint = ContactPoint::query()
                ->where('normalized_address', $normalizedAddress)
                ->first();

            $wasNewUser = false;

            if ($contactPoint === null) {
                $user = User::query()->create([
                    'display_name' => Str::of($normalizedAddress)->before('@')->replace(['.', '_'], ' ')->title(),
                    'email' => $channel === 'email' ? $normalizedAddress : null,
                    'email_verified_at' => $channel === 'email' ? $receivedAt : null,
                    'timezone' => config('weightsy.default_timezone'),
                    'onboarding_completed_at' => $receivedAt,
                ]);

                $contactPoint = $user->contactPoints()->create([
                    'channel' => $channel,
                    'address' => $inbound->from,
                    'normalized_address' => $normalizedAddress,
                    'receives_reminders' => false,
                    'verified_at' => $receivedAt,
                    'last_inbound_at' => $receivedAt,
                ]);

                $wasNewUser = true;
            } else {
                $user = $contactPoint->user;

                $contactPoint->update([
                    'last_inbound_at' => $receivedAt,
                    'receives_reminders' => $user->notification_confirmed_at !== null && $user->unsubscribed_at === null,
                ]);
            }

            $message = Message::query()->create([
                'user_id' => $user->id,
                'contact_point_id' => $contactPoint->id,
                'direction' => 'inbound',
                'channel' => $channel,
                'provider' => $inbound->provider,
                'external_id' => $inbound->externalId,
                'subject' => $inbound->subject,
                'body_text' => $body,
                'parsed_status' => 'pending',
                'received_at' => $receivedAt,
                'metadata' => $inbound->metadata,
            ]);

            $localizedTimestamp = $receivedAt->setTimezone($user->timezone);
            $reminderTime = $user->reminder_time_local ?? $localizedTimestamp->format('H:i:s');

            $checkin = Checkin::query()->create([
                'user_id' => $user->id,
                'contact_point_id' => $contactPoint->id,
                'metric_type' => $parsed->metricType,
                'value_decimal' => $parsed->valueDecimal,
                'systolic' => $parsed->systolic,
                'diastolic' => $parsed->diastolic,
                'occurred_on' => $localizedTimestamp->toDateString(),
                'received_at' => $receivedAt,
                'source_type' => 'inbound_message',
                'raw_input' => $body,
            ]);

            $user->update([
                'last_checkin_at' => $receivedAt,
                'reminder_time_local' => $reminderTime,
            ]);

            $this->scheduleManager->syncForUser(
                $user,
                $user->notification_confirmed_at !== null && $user->unsubscribed_at === null ? 'active' : 'pending',
                $receivedAt,
            );

            $message->update([
                'parsed_status' => 'parsed',
                'processed_at' => now(),
                'metadata' => array_merge($inbound->metadata, [
                    'metric_type' => $parsed->metricType,
                    'checkin_id' => $checkin->id,
                    'normalized_display' => $parsed->normalizedDisplay,
                ]),
            ]);

            return new InboundProcessingResult(
                recognized: true,
                createdUser: $wasNewUser,
                user: $user,
                checkin: $checkin,
                message: $message,
                parsedCheckin: $parsed,
            );
        });
    }
}
