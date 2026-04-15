<?php

namespace App\Services;

use App\DataTransferObjects\InboundMessageData;
use App\DataTransferObjects\InboundProcessingResult;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class InboundCheckinResponder
{
    public function __construct(
        private readonly OutboundMessageLogger $messageLogger,
    ) {}

    public function send(InboundMessageData $inbound, InboundProcessingResult $result): void
    {
        if ($result->duplicate) {
            return;
        }

        $minutes = config('weightsy.signing.minutes', 10080);

        if ($result->recognized && $result->parsedCheckin !== null) {
            if ($result->createdUser && $result->user !== null) {
                $confirmUrl = URL::temporarySignedRoute('onboarding.confirm', now()->addMinutes($minutes), ['user' => $result->user]);
                $settingsUrl = URL::temporarySignedRoute('onboarding.edit', now()->addMinutes($minutes), ['user' => $result->user]);
                $unsubscribeUrl = URL::temporarySignedRoute('onboarding.unsubscribe', now()->addMinutes($minutes), ['user' => $result->user]);

                $subject = 'Confirm your Weightsy reminders';
                $body = implode("\n", [
                    'Your first Weightsy check-in was recorded as '.$result->parsedCheckin->normalizedDisplay.'.',
                    '',
                    'Before we start sending reminders, please confirm that you want them.',
                    '',
                    'Confirm reminders: '.$confirmUrl,
                    'Change reminder time: '.$settingsUrl,
                    'Unsubscribe: '.$unsubscribeUrl,
                    '',
                    'If you do nothing, we will keep your data but will not start recurring reminder emails.',
                ]);
            } else {
                return;
            }
        } else {
            $subject = 'Weightsy check-in help';
            $body = implode("\n", [
                'We could not read that check-in.',
                '',
                'Reply with one of these formats:',
                '123',
                '120/70',
                '14.0%',
            ]);
        }

        $contactPoint = $result->message->contactPoint;

        Mail::raw($body, function ($message) use ($inbound, $subject) {
            $message->to($inbound->from)->subject($subject);
        });

        $this->messageLogger->log(
            user: $result->user,
            contactPoint: $contactPoint,
            channel: $inbound->channel,
            provider: 'smtp',
            to: $inbound->from,
            subject: $subject,
            body: $body,
            metadata: [
                'category' => $result->recognized ? 'onboarding' : 'checkin_help',
            ],
            inReplyTo: $result->message->external_id,
        );
    }
}
