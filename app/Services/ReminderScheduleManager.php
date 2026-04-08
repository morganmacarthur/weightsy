<?php

namespace App\Services;

use App\Models\ReminderSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;

class ReminderScheduleManager
{
    public function nextRunAt(string $timezone, string $remindAtLocal, ?CarbonImmutable $fromUtc = null): CarbonImmutable
    {
        $fromUtc ??= CarbonImmutable::now('UTC');
        $localNow = $fromUtc->setTimezone($timezone);

        $candidate = $localNow->setTimeFromTimeString($remindAtLocal);

        if ($candidate->lessThanOrEqualTo($localNow)) {
            $candidate = $candidate->addDay();
        }

        return $candidate->setTimezone('UTC');
    }

    public function syncForUser(User $user, string $status, ?CarbonImmutable $fromUtc = null): void
    {
        $contactPoint = $user->contactPoints()->first();

        if (! $contactPoint || ! $user->reminder_time_local) {
            return;
        }

        ReminderSchedule::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'contact_point_id' => $contactPoint->id,
            ],
            [
                'status' => $status,
                'cadence' => 'daily',
                'timezone' => $user->timezone,
                'remind_at_local' => $user->reminder_time_local,
                'next_run_at' => $this->nextRunAt($user->timezone, $user->reminder_time_local, $fromUtc),
            ],
        );
    }
}
