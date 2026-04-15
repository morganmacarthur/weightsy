<?php

namespace Tests\Feature;

use App\DataTransferObjects\InboundMessageData;
use App\Models\Checkin;
use App\Models\ContactPoint;
use App\Models\Message;
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

    public function test_recorded_email_contains_a_magic_link(): void
    {
        Mail::fake();

        $processor = app(InboundCheckinProcessor::class);
        $responder = app(InboundCheckinResponder::class);

        $firstInbound = new InboundMessageData(
            externalId: 'first-1',
            from: 'checker@example.com',
            channel: 'email',
            subject: 'first',
            text: '123',
            receivedAt: now()->subDay()->toImmutable(),
            provider: 'test',
            metadata: [],
        );

        $firstResult = $processor->process($firstInbound);
        $responder->send($firstInbound, $firstResult);

        $secondInbound = new InboundMessageData(
            externalId: 'second-1',
            from: 'checker@example.com',
            channel: 'email',
            subject: 'second',
            text: '124',
            receivedAt: now()->toImmutable(),
            provider: 'test',
            metadata: [],
        );

        $secondResult = $processor->process($secondInbound);
        $responder->send($secondInbound, $secondResult);

        $outbound = Message::query()
            ->where('direction', 'outbound')
            ->where('subject', 'Recorded: 124')
            ->latest('id')
            ->first();

        $this->assertNotNull($outbound);
        $this->assertStringContainsString('/app/login/', $outbound->body_text);
        $this->assertStringContainsString('Open your timeline:', $outbound->body_text);
        $this->assertTrue(\App\Models\LoginToken::query()->where('user_id', $secondResult->user?->id)->exists());
    }
}
