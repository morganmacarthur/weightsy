<?php

namespace Tests\Feature;

use App\Contracts\InboundMailbox;
use App\DataTransferObjects\InboundMailboxMessage;
use App\Models\ContactPoint;
use App\Models\Message;
use App\Models\User;
use App\Services\InboundCheckinResponder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Fakes\FakeInboundCheckinResponder;
use Tests\Fakes\FakeInboundMailbox;
use Tests\TestCase;

class PollImapInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_polls_the_mailbox_processes_messages_and_marks_them_done(): void
    {
        $mailbox = new FakeInboundMailbox([
            new InboundMailboxMessage(
                uid: '101',
                from: 'sender@example.com',
                subject: 'check-in',
                text: '123',
                receivedAt: now()->toImmutable(),
                rawHeaders: 'From: sender@example.com',
                rawBody: '123',
            ),
            new InboundMailboxMessage(
                uid: '102',
                from: '5551234567@vtext.com',
                subject: 'bp',
                text: '120/70',
                receivedAt: now()->toImmutable(),
                rawHeaders: 'From: 5551234567@vtext.com',
                rawBody: '120/70',
            ),
        ]);
        $responder = new FakeInboundCheckinResponder;

        $this->app->instance(InboundMailbox::class, $mailbox);
        $this->app->instance(InboundCheckinResponder::class, $responder);

        $this->artisan('weightsy:imap:poll --limit=10 --delete')
            ->expectsOutputToContain('Processed UID 101')
            ->expectsOutputToContain('Processed UID 102')
            ->assertSuccessful();

        $this->assertSame(2, User::query()->count());
        $this->assertCount(2, $mailbox->marked);
        $this->assertSame([
            ['uid' => '101', 'delete' => true],
            ['uid' => '102', 'delete' => true],
        ], $mailbox->marked);
        $this->assertCount(2, $responder->sent);
    }

    public function test_it_does_not_send_recorded_reply_for_existing_user_checkin(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'sender@example.com',
            'notification_confirmed_at' => now(),
        ]);

        ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'sender@example.com',
            'normalized_address' => 'sender@example.com',
            'receives_reminders' => true,
        ]);

        $mailbox = new FakeInboundMailbox([
            new InboundMailboxMessage(
                uid: '101',
                from: 'sender@example.com',
                subject: 'check-in',
                text: '123',
                receivedAt: now()->toImmutable(),
                rawHeaders: 'From: sender@example.com',
                rawBody: '123',
            ),
        ]);

        $this->app->instance(InboundMailbox::class, $mailbox);

        $this->artisan('weightsy:imap:poll --limit=10 --delete')
            ->expectsOutputToContain('Processed UID 101')
            ->assertSuccessful();

        $this->assertSame(0, Message::query()->where('direction', 'outbound')->count());
    }

    public function test_it_parses_a_reply_above_quoted_original_content(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'sender@example.com',
            'notification_confirmed_at' => now(),
        ]);

        ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'sender@example.com',
            'normalized_address' => 'sender@example.com',
            'receives_reminders' => true,
        ]);

        $mailbox = new FakeInboundMailbox([
            new InboundMailboxMessage(
                uid: '201',
                from: 'sender@example.com',
                subject: 'Re: Weightsy check-in reminder',
                text: "345\n\nOn Mon, Apr 13, 2026 at 5:32 AM Weightsy <update@weightsy.com> wrote:\n> Time for today's Weightsy check-in.\n> \n> Reply with one of these:\n> 123",
                receivedAt: now()->toImmutable(),
                rawHeaders: 'From: sender@example.com',
                rawBody: "345\n\nOn Mon, Apr 13, 2026 at 5:32 AM Weightsy <update@weightsy.com> wrote:\n> Time for today's Weightsy check-in.\n> \n> Reply with one of these:\n> 123",
            ),
        ]);

        $this->app->instance(InboundMailbox::class, $mailbox);

        $this->artisan('weightsy:imap:poll --limit=10 --delete')
            ->expectsOutputToContain('Processed UID 201 from sender@example.com (345)')
            ->assertSuccessful();

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'metric_type' => 'weight',
            'raw_input' => "345\n\nOn Mon, Apr 13, 2026 at 5:32 AM Weightsy <update@weightsy.com> wrote:\n> Time for today's Weightsy check-in.\n> \n> Reply with one of these:\n> 123",
        ]);

        $this->assertSame(0, Message::query()->where('direction', 'outbound')->count());
    }
}
