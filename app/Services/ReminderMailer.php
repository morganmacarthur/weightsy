<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ReminderMailer
{
    public function __construct(
        private readonly OutboundMessageLogger $messageLogger,
    ) {
    }

    public function send(User $user): void
    {
        $contactPoint = $user->contactPoints()
            ->where('channel', 'email')
            ->orderByDesc('receives_reminders')
            ->orderBy('id')
            ->first();
        $to = $user->email ?? $contactPoint?->address;

        if (! $to) {
            return;
        }

        $subject = 'Weightsy check-in reminder';
        $unsubscribeUrl = URL::temporarySignedRoute(
            'onboarding.unsubscribe',
            now()->addMinutes(config('weightsy.signing.minutes', 10080)),
            ['user' => $user]
        );

        $body = implode("\n", [
            'Time for today\'s Weightsy check-in.',
            '',
            'Reply with one of these:',
            '123',
            '120/70',
            '14.0%',
            '',
            'Unsubscribe: '.$unsubscribeUrl,
        ]);

        Mail::raw($body, function ($message) use ($to) {
            $message->to($to)->subject('Weightsy check-in reminder');
        });

        $this->messageLogger->log(
            user: $user,
            contactPoint: $contactPoint,
            channel: 'email',
            provider: 'smtp',
            to: $to,
            subject: $subject,
            body: $body,
            metadata: [
                'category' => 'reminder',
            ],
        );
    }
}
