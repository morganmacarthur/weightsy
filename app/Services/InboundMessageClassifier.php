<?php

namespace App\Services;

use App\DataTransferObjects\InboundMailboxMessage;
use Illuminate\Support\Str;

class InboundMessageClassifier
{
    public function shouldSkip(InboundMailboxMessage $message): bool
    {
        $from = Str::lower(trim($message->from));
        $subject = Str::lower(trim((string) $message->subject));
        $body = Str::lower(trim($message->text));

        if ($from === '') {
            return true;
        }

        if (str_contains($from, 'mailer-daemon') || str_contains($from, 'postmaster')) {
            return true;
        }

        $bouncePhrases = [
            'delivery status notification',
            'mail delivery failed',
            'undeliverable',
            'returned mail',
            'failure notice',
        ];

        foreach ($bouncePhrases as $phrase) {
            if (str_contains($subject, $phrase) || str_contains($body, $phrase)) {
                return true;
            }
        }

        return false;
    }
}
