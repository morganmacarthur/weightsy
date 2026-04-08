<?php

namespace Tests\Feature;

use App\Contracts\InboundMailbox;
use App\DataTransferObjects\InboundMailboxMessage;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeInboundMailbox;
use Tests\TestCase;

class PollImapBounceFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_mailer_daemon_messages(): void
    {
        $mailbox = new FakeInboundMailbox([
            new InboundMailboxMessage(
                uid: '500',
                from: 'Mailer-Daemon@example.com',
                subject: 'Mail delivery failed: returning message to sender',
                text: 'Delivery Status Notification',
                receivedAt: now()->toImmutable(),
                rawHeaders: 'From: Mailer-Daemon@example.com',
                rawBody: 'Delivery Status Notification',
            ),
        ]);

        $this->app->instance(InboundMailbox::class, $mailbox);

        $this->artisan('weightsy:imap:poll --limit=5')
            ->expectsOutputToContain('Skipped UID 500')
            ->assertSuccessful();

        $this->assertCount(1, $mailbox->marked);
        $this->assertSame(0, Message::query()->count());
    }
}
