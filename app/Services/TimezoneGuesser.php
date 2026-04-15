<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class TimezoneGuesser
{
    public function guess(?CarbonImmutable $receivedAt): string
    {
        if ($receivedAt === null) {
            return config('weightsy.default_timezone', 'America/Los_Angeles');
        }

        $offsetHours = (int) round($receivedAt->utcOffset() / 60);

        return match ($offsetHours) {
            -8, -7 => 'America/Los_Angeles',
            -6, -5 => 'America/Chicago',
            -4 => 'America/New_York',
            -10 => 'Pacific/Honolulu',
            0 => 'UTC',
            1 => 'Europe/London',
            default => config('weightsy.default_timezone', 'America/Los_Angeles'),
        };
    }

    public function options(): array
    {
        return [
            'America/Los_Angeles' => 'Pacific',
            'America/Denver' => 'Mountain',
            'America/Chicago' => 'Central',
            'America/New_York' => 'Eastern',
            'Pacific/Honolulu' => 'Hawaii',
            'UTC' => 'UTC',
        ];
    }
}
