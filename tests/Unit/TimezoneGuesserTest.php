<?php

namespace Tests\Unit;

use App\Services\TimezoneGuesser;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class TimezoneGuesserTest extends TestCase
{
    public function test_it_guesses_pacific_from_a_negative_seven_hour_offset(): void
    {
        $guesser = app(TimezoneGuesser::class);

        $timezone = $guesser->guess(CarbonImmutable::parse('2026-04-07T08:00:00-07:00'));

        $this->assertSame('America/Los_Angeles', $timezone);
    }

    public function test_it_guesses_eastern_from_a_negative_four_hour_offset(): void
    {
        $guesser = app(TimezoneGuesser::class);

        $timezone = $guesser->guess(CarbonImmutable::parse('2026-04-07T08:00:00-04:00'));

        $this->assertSame('America/New_York', $timezone);
    }
}
