<?php

namespace Tests\Feature;

use App\Models\ContactPoint;
use App\Models\ReminderSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_link_activates_reminders(): void
    {
        $user = User::factory()->create([
            'reminder_time_local' => '08:30:00',
            'notification_confirmed_at' => null,
        ]);

        ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => false,
        ]);

        ReminderSchedule::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $user->contactPoints()->first()->id,
            'status' => 'pending',
            'cadence' => 'daily',
            'timezone' => $user->timezone,
            'remind_at_local' => '08:30:00',
        ]);

        $url = URL::temporarySignedRoute('onboarding.confirm', now()->addHour(), ['user' => $user]);

        $this->get($url)->assertOk();

        $user->refresh();

        $this->assertNotNull($user->notification_confirmed_at);
        $this->assertNull($user->unsubscribed_at);
        $this->assertDatabaseHas('contact_points', [
            'user_id' => $user->id,
            'receives_reminders' => true,
        ]);
        $this->assertDatabaseHas('reminder_schedules', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $this->assertNotNull(ReminderSchedule::query()->where('user_id', $user->id)->value('next_run_at'));
    }

    public function test_unsubscribe_link_disables_reminders(): void
    {
        $user = User::factory()->create([
            'notification_confirmed_at' => now(),
            'unsubscribed_at' => null,
        ]);

        ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => true,
        ]);

        ReminderSchedule::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $user->contactPoints()->first()->id,
            'status' => 'active',
            'cadence' => 'daily',
            'timezone' => $user->timezone,
            'remind_at_local' => '08:30:00',
        ]);

        $url = URL::temporarySignedRoute('onboarding.unsubscribe', now()->addHour(), ['user' => $user]);

        $this->get($url)->assertOk();

        $user->refresh();

        $this->assertNotNull($user->unsubscribed_at);
        $this->assertDatabaseHas('contact_points', [
            'user_id' => $user->id,
            'receives_reminders' => false,
        ]);
        $this->assertDatabaseHas('reminder_schedules', [
            'user_id' => $user->id,
            'status' => 'unsubscribed',
        ]);
    }

    public function test_updating_time_recomputes_next_run_at_and_confirms_reminders(): void
    {
        $user = User::factory()->create([
            'reminder_time_local' => '08:30:00',
            'notification_confirmed_at' => null,
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => false,
        ]);

        ReminderSchedule::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'status' => 'pending',
            'cadence' => 'daily',
            'timezone' => $user->timezone,
            'remind_at_local' => '08:30:00',
            'next_run_at' => now()->subHour(),
        ]);

        $url = URL::temporarySignedRoute('onboarding.update', now()->addHour(), ['user' => $user]);

        $this->post($url, [
            'timezone' => 'America/New_York',
            'reminder_time_local' => '07:15',
        ])->assertRedirect();

        $user->refresh();
        $schedule = ReminderSchedule::query()->where('user_id', $user->id)->first();

        $this->assertSame('07:15:00', $user->reminder_time_local);
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertNotNull($user->notification_confirmed_at);
        $this->assertSame('active', $schedule->status);
        $this->assertSame('07:15:00', $schedule->remind_at_local);
        $this->assertSame('America/New_York', $schedule->timezone);
        $this->assertNotNull($schedule->next_run_at);
    }
}
