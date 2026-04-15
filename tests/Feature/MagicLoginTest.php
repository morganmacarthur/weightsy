<?php

namespace Tests\Feature;

use App\DataTransferObjects\InboundMessageData;
use App\Models\Checkin;
use App\Models\ContactPoint;
use App\Models\User;
use App\Services\InboundCheckinProcessor;
use App\Services\InboundCheckinResponder;
use App\Services\MagicLoginLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_magic_link_logs_user_in_and_redirects_to_timeline(): void
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

        Checkin::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $user->contactPoints()->first()->id,
            'metric_type' => 'weight',
            'value_decimal' => '344.00',
            'occurred_on' => now()->toDateString(),
            'received_at' => now(),
            'source_type' => 'inbound_message',
            'raw_input' => '344',
        ]);

        $url = app(MagicLoginLinkService::class)->createForUser($user);

        $this->get($url)
            ->assertRedirect(route('timeline.show'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('timeline.show'))
            ->assertOk()
            ->assertSee('344');
    }

    public function test_existing_user_checkin_does_not_send_recorded_reply_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'checker@example.com',
            'notification_confirmed_at' => now(),
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'checker@example.com',
            'normalized_address' => 'checker@example.com',
            'receives_reminders' => true,
        ]);

        $processor = app(InboundCheckinProcessor::class);
        $responder = app(InboundCheckinResponder::class);

        $inbound = new InboundMessageData(
            externalId: 'ext-existing-1',
            from: 'checker@example.com',
            channel: 'email',
            subject: 'check-in',
            text: '124',
            receivedAt: now()->toImmutable(),
            provider: 'test',
            metadata: [],
        );

        $result = $processor->process($inbound);
        $responder->send($inbound, $result);

        Mail::assertNothingSent();
        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'raw_input' => '124',
        ]);
    }
}
