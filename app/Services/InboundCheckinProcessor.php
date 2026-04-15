<?php

namespace App\Services;

use App\DataTransferObjects\InboundMessageData;
use App\DataTransferObjects\InboundProcessingResult;
use App\DataTransferObjects\ParsedCheckin;
use App\Models\Checkin;
use App\Models\ContactPoint;
use App\Models\Message;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboundCheckinProcessor
{
    public function __construct(
        private readonly CheckinMessageParser $parser,
        private readonly ContactChannelGuesser $channelGuesser,
        private readonly ReminderScheduleManager $scheduleManager,
        private readonly TimezoneGuesser $timezoneGuesser,
        private readonly EmailReplyParser $replyParser,
    ) {}

    public function process(InboundMessageData $inbound): InboundProcessingResult
    {
        $body = trim($inbound->text);
        $candidate = $this->replyParser->extractCheckinCandidate($body);
        $parsed = $this->parser->parse($candidate);
        $receivedAt = $inbound->receivedAt ?? CarbonImmutable::now();
        $channel = $inbound->channel ?: $this->channelGuesser->guess($inbound->from);
        $normalizedAddress = Str::lower(trim($inbound->from));

        return DB::transaction(function () use ($body, $candidate, $parsed, $receivedAt, $channel, $normalizedAddress, $inbound) {
            if ($inbound->externalId !== null) {
                $existing = Message::query()
                    ->where('direction', 'inbound')
                    ->where('provider', $inbound->provider)
                    ->where('external_id', $inbound->externalId)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $this->inboundResultFromExistingMessage($existing);
                }
            }

            if ($parsed === null) {
                try {
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
                        'metadata' => array_merge($inbound->metadata, [
                            'parse_candidate' => $candidate,
                        ]),
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $existing = Message::query()
                        ->where('direction', 'inbound')
                        ->where('provider', $inbound->provider)
                        ->where('external_id', $inbound->externalId)
                        ->firstOrFail();

                    return $this->inboundResultFromExistingMessage($existing);
                }

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
                    'timezone' => $this->timezoneGuesser->guess($receivedAt),
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

            try {
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
            } catch (UniqueConstraintViolationException) {
                $existing = Message::query()
                    ->where('direction', 'inbound')
                    ->where('provider', $inbound->provider)
                    ->where('external_id', $inbound->externalId)
                    ->firstOrFail();

                return $this->inboundResultFromExistingMessage($existing);
            }

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
                    'parse_candidate' => $candidate,
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

    private function inboundResultFromExistingMessage(Message $message): InboundProcessingResult
    {
        if ($message->parsed_status === 'unrecognized') {
            return new InboundProcessingResult(
                recognized: false,
                createdUser: false,
                user: $message->user,
                checkin: null,
                message: $message,
                parsedCheckin: null,
                duplicate: true,
            );
        }

        $checkinId = $message->metadata['checkin_id'] ?? null;
        $checkin = $checkinId !== null ? Checkin::query()->find($checkinId) : null;
        $user = $message->user ?? $checkin?->user;

        $parsedCheckin = null;
        if ($checkin !== null) {
            $parsedCheckin = new ParsedCheckin(
                metricType: $checkin->metric_type,
                valueDecimal: $checkin->value_decimal,
                systolic: $checkin->systolic,
                diastolic: $checkin->diastolic,
                normalizedDisplay: $checkin->editableInput(),
            );
        }

        return new InboundProcessingResult(
            recognized: $checkin !== null,
            createdUser: false,
            user: $user,
            checkin: $checkin,
            message: $message,
            parsedCheckin: $parsedCheckin,
            duplicate: true,
        );
    }
}
