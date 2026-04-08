<?php

namespace Tests\Unit;

use App\Services\ReminderScheduleManager;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ReminderScheduleManagerTest extends TestCase
{
    public function test_it_computes_the_next_utc_run_from_local_preference(): void
    {
        $manager = app(ReminderScheduleManager::class);

        $nextRunAt = $manager->nextRunAt(
            timezone: 'America/Los_Angeles',
            remindAtLocal: '08:30:00',
            fromUtc: CarbonImmutable::parse('2026-04-07 14:00:00', 'UTC'),
        );

        $this->assertSame('2026-04-07T15:30:00+00:00', $nextRunAt->toIso8601String());
    }

    public function test_it_rolls_to_the_next_day_when_today_has_passed(): void
    {
        $manager = app(ReminderScheduleManager::class);

        $nextRunAt = $manager->nextRunAt(
            timezone: 'America/Los_Angeles',
            remindAtLocal: '08:30:00',
            fromUtc: CarbonImmutable::parse('2026-04-07 18:00:00', 'UTC'),
        );

        $this->assertSame('2026-04-08T15:30:00+00:00', $nextRunAt->toIso8601String());
    }
}
