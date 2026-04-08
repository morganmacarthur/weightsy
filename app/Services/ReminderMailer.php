<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ReminderMailer
{
    public function send(User $user): void
    {
        $to = $user->email ?? $user->contactPoints()->value('address');

        if (! $to) {
            return;
        }

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
    }
}
