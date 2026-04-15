<?php

namespace App\Http\Controllers;

use App\Models\ReminderSchedule;
use App\Models\User;
use App\Services\ReminderScheduleManager;
use App\Services\TimezoneGuesser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly ReminderScheduleManager $scheduleManager,
        private readonly TimezoneGuesser $timezoneGuesser,
    ) {
    }

    public function confirm(User $user): View
    {
        $this->activateReminders($user);

        return view('onboarding.confirmed', [
            'user' => $user->fresh(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('onboarding.settings', [
            'user' => $user,
            'timezoneOptions' => $this->timezoneGuesser->options(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'reminder_time_local' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'string'],
        ]);

        $user->update([
            'reminder_time_local' => $validated['reminder_time_local'].':00',
            'timezone' => $validated['timezone'],
        ]);

        $this->activateReminders($user);

        return redirect()->back()->with('status', 'Reminder time updated and notifications confirmed.');
    }

    public function unsubscribe(User $user): View
    {
        $user->update([
            'unsubscribed_at' => now(),
        ]);

        ReminderSchedule::query()
            ->where('user_id', $user->id)
            ->update([
                'status' => 'unsubscribed',
                'next_run_at' => null,
            ]);

        $user->contactPoints()->update([
            'receives_reminders' => false,
        ]);

        return view('onboarding.unsubscribed', [
            'user' => $user->fresh(),
        ]);
    }

    private function activateReminders(User $user): void
    {
        $user->update([
            'notification_confirmed_at' => $user->notification_confirmed_at ?? now(),
            'unsubscribed_at' => null,
        ]);

        $user->contactPoints()->update([
            'receives_reminders' => true,
        ]);

        ReminderSchedule::query()
            ->where('user_id', $user->id)
            ->delete();

        $this->scheduleManager->syncForUser($user->fresh(), 'active');
    }
}
