<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\ContactPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineCheckinTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_a_manual_checkin(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.com',
        ]);

        ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => true,
        ]);

        $this->actingAs($user)
            ->post(route('timeline.checkins.store'), [
                'input' => '14.0%',
                'occurred_on' => '2026-04-13',
                'notes' => 'After workout',
            ])
            ->assertRedirect(route('timeline.show'));

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'metric_type' => 'body_fat',
            'occurred_on' => '2026-04-13 00:00:00',
            'raw_input' => '14.0%',
            'notes' => 'After workout',
            'source_type' => 'manual_entry',
        ]);
    }

    public function test_authenticated_user_can_edit_their_checkin(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.com',
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => true,
        ]);

        $checkin = Checkin::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'metric_type' => 'weight',
            'value_decimal' => '344.00',
            'occurred_on' => '2026-04-12',
            'received_at' => now(),
            'source_type' => 'inbound_message',
            'raw_input' => '344',
        ]);

        $this->actingAs($user)
            ->patch(route('timeline.checkins.update', $checkin), [
                'input' => '120/70',
                'occurred_on' => '2026-04-11',
                'notes' => 'Manual correction',
            ])
            ->assertRedirect(route('timeline.show'));

        $this->assertDatabaseHas('checkins', [
            'id' => $checkin->id,
            'metric_type' => 'blood_pressure',
            'systolic' => 120,
            'diastolic' => 70,
            'occurred_on' => '2026-04-11 00:00:00',
            'notes' => 'Manual correction',
            'source_type' => 'manual_edit',
        ]);
    }

    public function test_timeline_page_shows_add_and_edit_actions(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.com',
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => true,
        ]);

        $checkin = Checkin::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'metric_type' => 'weight',
            'value_decimal' => '344.00',
            'occurred_on' => '2026-04-12',
            'received_at' => now(),
            'source_type' => 'inbound_message',
            'raw_input' => '344',
        ]);

        $this->actingAs($user)
            ->get(route('timeline.show', ['edit' => $checkin->id]))
            ->assertOk()
            ->assertSee('Add check-in')
            ->assertSee('History graph')
            ->assertSee('Edit check-in')
            ->assertSee('344');
    }

    public function test_edit_form_uses_normalized_value_instead_of_raw_inbound_reply_body(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.com',
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'person@example.com',
            'normalized_address' => 'person@example.com',
            'receives_reminders' => true,
        ]);

        $rawReply = "338\nOn Mon, Apr 13, 2026 at 11:00 PM Weightsy <update@weightsy.com> wrote:\n> Time for today's Weightsy check-in.";

        $checkin = Checkin::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'metric_type' => 'weight',
            'value_decimal' => '338.00',
            'occurred_on' => '2026-04-13',
            'received_at' => now(),
            'source_type' => 'inbound_message',
            'raw_input' => $rawReply,
        ]);

        $this->actingAs($user)
            ->get(route('timeline.show', ['edit' => $checkin->id]))
            ->assertOk()
            ->assertSee('value="338"', false)
            ->assertDontSee($rawReply);
    }
}
