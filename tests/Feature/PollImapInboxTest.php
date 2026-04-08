<?php

namespace Tests\Feature;

use App\Contracts\InboundMailbox;
use App\DataTransferObjects\InboundMailboxMessage;
use App\Services\InboundCheckinResponder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $responder = new FakeInboundCheckinResponder();

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
}
