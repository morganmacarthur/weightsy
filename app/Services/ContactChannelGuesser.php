<?php

namespace App\Services;

use Illuminate\Support\Str;

class ContactChannelGuesser
{
    private const MMS_DOMAINS = [
        'vtext.com',
        'vzwpix.com',
        'tmomail.net',
        'txt.att.net',
        'mms.att.net',
        'messaging.sprintpcs.com',
    ];

    public function guess(string $address): string
    {
        $normalizedAddress = Str::lower(trim($address));

        if (str_contains($normalizedAddress, '@')) {
            $domain = Str::of($normalizedAddress)->after('@')->toString();

            return in_array($domain, self::MMS_DOMAINS, true) ? 'mms' : 'email';
        }

        return 'mms';
    }
}
