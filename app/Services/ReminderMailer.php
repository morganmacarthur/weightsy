<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class ReminderMailer
{
    public function __construct(
        private readonly OutboundMessageLogger $messageLogger,
        private readonly MagicLoginLinkService $magicLoginLinkService,
    ) {}

    /**
     * @return array{sent: bool, reason: string|null}
     */
    public function send(User $user): array
    {
        $contactPoint = $user->contactPoints()
            ->where('channel', 'email')
            ->orderByDesc('receives_reminders')
            ->orderBy('id')
            ->first();
        $to = $user->email ?? $contactPoint?->address;

        if (! $to) {
            return [
                'sent' => false,
                'reason' => 'no_recipient',
            ];
        }

        $subject = 'Weightsy check-in reminder';
        $unsubscribeUrl = URL::temporarySignedRoute(
            'onboarding.unsubscribe',
            now()->addMinutes(config('weightsy.signing.minutes', 10080)),
            ['user' => $user]
        );

        $timelineUrl = $this->magicLoginLinkService->createForUser($user);

        $body = implode("\n", [
            'Time for today\'s Weightsy check-in.',
            '',
            'Reply with one of these:',
            '123',
            '120/70',
            '14.0%',
            '',
            'After your check-in, you can view your progress here: '.$timelineUrl,
            '',
            'Unsubscribe: '.$unsubscribeUrl,
        ]);

        try {
            Mail::raw($body, function ($message) use ($to) {
                $message->to($to)->subject('Weightsy check-in reminder');
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'reason' => 'mail_exception: '.$e->getMessage(),
            ];
        }

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

        return [
            'sent' => true,
            'reason' => null,
        ];
    }
}
