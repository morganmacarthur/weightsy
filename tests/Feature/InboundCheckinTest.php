<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundCheckinTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_checkin_creates_the_user_and_daily_reminder(): void
    {
        $response = $this->postJson('/app/inbound/checkins', [
            'from' => 'checker@example.com',
            'text' => '123',
            'received_at' => '2026-04-03T08:30:00-07:00',
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'status' => 'recorded',
                'created_user' => true,
                'metric_type' => 'weight',
                'normalized_input' => '123',
            ]);

        $user = User::query()->firstOrFail();

        $this->assertSame('checker@example.com', $user->email);
        $this->assertSame('America/Los_Angeles', $user->timezone);
        $this->assertSame('08:30:00', $user->reminder_time_local);
        $this->assertNull($user->notification_confirmed_at);

        $this->assertDatabaseHas('contact_points', [
            'user_id' => $user->id,
            'normalized_address' => 'checker@example.com',
            'channel' => 'email',
            'receives_reminders' => false,
        ]);

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'metric_type' => 'weight',
            'raw_input' => '123',
        ]);

        $this->assertDatabaseHas('reminder_schedules', [
            'user_id' => $user->id,
            'status' => 'pending',
            'cadence' => 'daily',
        ]);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'direction' => 'inbound',
            'parsed_status' => 'parsed',
        ]);
    }

    public function test_existing_sender_reuses_the_same_user(): void
    {
        $this->postJson('/app/inbound/checkins', [
            'from' => '5551234567@vtext.com',
            'channel' => 'mms',
            'text' => '14.0%',
        ])->assertCreated();

        $this->postJson('/app/inbound/checkins', [
            'from' => '5551234567@vtext.com',
            'channel' => 'mms',
            'text' => '120/70',
        ])->assertOk();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(2, Checkin::query()->count());
    }

    public function test_invalid_checkins_are_rejected(): void
    {
        $this->postJson('/app/inbound/checkins', [
            'from' => 'checker@example.com',
            'text' => 'hello there',
        ])->assertUnprocessable();

        $this->assertSame(0, User::query()->count());
    }
}
