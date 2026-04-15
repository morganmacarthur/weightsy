<?php

namespace Tests\Feature;

use App\Models\ContactPoint;
use App\Models\Message;
use App\Models\ReminderSchedule;
use App\Models\User;
use App\Services\MagicLoginLinkService;
use App\Services\OutboundMessageLogger;
use App\Services\ReminderMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendReminderEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_reminders_only_to_confirmed_active_users(): void
    {
        $activeUser = User::factory()->create([
            'email' => 'active@example.com',
            'notification_confirmed_at' => now(),
            'unsubscribed_at' => null,
        ]);

        $activeContact = ContactPoint::query()->create([
            'user_id' => $activeUser->id,
            'channel' => 'email',
            'address' => 'active@example.com',
            'normalized_address' => 'active@example.com',
            'receives_reminders' => true,
        ]);

        $activeSchedule = ReminderSchedule::query()->create([
            'user_id' => $activeUser->id,
            'contact_point_id' => $activeContact->id,
            'status' => 'active',
            'cadence' => 'daily',
            'timezone' => $activeUser->timezone,
            'remind_at_local' => '08:30:00',
            'next_run_at' => now()->subMinute(),
        ]);

        $pendingUser = User::factory()->create([
            'email' => 'pending@example.com',
            'notification_confirmed_at' => null,
        ]);

        $pendingContact = ContactPoint::query()->create([
            'user_id' => $pendingUser->id,
            'channel' => 'email',
            'address' => 'pending@example.com',
            'normalized_address' => 'pending@example.com',
            'receives_reminders' => false,
        ]);

        $pendingSchedule = ReminderSchedule::query()->create([
            'user_id' => $pendingUser->id,
            'contact_point_id' => $pendingContact->id,
            'status' => 'pending',
            'cadence' => 'daily',
            'timezone' => $pendingUser->timezone,
            'remind_at_local' => '08:30:00',
            'next_run_at' => now()->subMinute(),
        ]);

        $fakeMailer = new class extends ReminderMailer
        {
            public array $sentTo = [];

            public function __construct()
            {
                parent::__construct(
                    new OutboundMessageLogger,
                    new MagicLoginLinkService,
                );
            }

            public function send(User $user): array
            {
                $this->sentTo[] = $user->email;

                return [
                    'sent' => true,
                    'reason' => null,
                ];
            }
        };

        $this->app->instance(ReminderMailer::class, $fakeMailer);

        $this->artisan('weightsy:reminders:send')
            ->expectsOutputToContain('Sent reminder to active@example.com')
            ->assertSuccessful();

        $activeSchedule->refresh();
        $pendingSchedule->refresh();

        $this->assertSame(['active@example.com'], $fakeMailer->sentTo);
        $this->assertNotNull($activeSchedule->last_sent_for_date);
        $this->assertNull($pendingSchedule->last_sent_for_date);
    }

    public function test_it_records_outbound_messages_for_sent_reminders(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'active@example.com',
            'notification_confirmed_at' => now(),
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'active@example.com',
            'normalized_address' => 'active@example.com',
            'receives_reminders' => true,
        ]);

        ReminderSchedule::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'status' => 'active',
            'cadence' => 'daily',
            'timezone' => $user->timezone,
            'remind_at_local' => '08:30:00',
            'next_run_at' => now()->subMinute(),
        ]);

        $this->artisan('weightsy:reminders:send')
            ->expectsOutputToContain('Sent reminder to active@example.com')
            ->assertSuccessful();

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'direction' => 'outbound',
            'provider' => 'smtp',
            'subject' => 'Weightsy check-in reminder',
        ]);

        $this->assertSame(1, Message::query()->where('direction', 'outbound')->count());
        $this->assertNotNull($contact->fresh()->last_outbound_at);

        $outboundBody = Message::query()->where('direction', 'outbound')->value('body_text');
        $this->assertStringContainsString('After your check-in, you can view your progress here:', (string) $outboundBody);
        $this->assertStringContainsString('/app/login/', (string) $outboundBody);
    }

    public function test_it_defers_next_run_and_logs_when_reminder_cannot_be_sent(): void
    {
        $user = User::factory()->create([
            'email' => 'broken@example.com',
            'notification_confirmed_at' => now(),
        ]);

        $contact = ContactPoint::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'address' => 'broken@example.com',
            'normalized_address' => 'broken@example.com',
            'receives_reminders' => true,
        ]);

        $schedule = ReminderSchedule::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $contact->id,
            'status' => 'active',
            'cadence' => 'daily',
            'timezone' => $user->timezone,
            'remind_at_local' => '08:30:00',
            'next_run_at' => now()->subMinute(),
            'reminder_failure_count' => 0,
        ]);

        $dueAt = $schedule->next_run_at->copy();

        $fakeMailer = new class extends ReminderMailer
        {
            public function __construct()
            {
                parent::__construct(
                    new OutboundMessageLogger,
                    new MagicLoginLinkService,
                );
            }

            public function send(User $user): array
            {
                return [
                    'sent' => false,
                    'reason' => 'no_recipient',
                ];
            }
        };

        $this->app->instance(ReminderMailer::class, $fakeMailer);

        $this->artisan('weightsy:reminders:send')
            ->assertSuccessful();

        $schedule->refresh();

        $this->assertSame(1, $schedule->reminder_failure_count);
        $this->assertSame('no_recipient', $schedule->last_reminder_failure_reason);
        $this->assertNotNull($schedule->last_reminder_failure_at);
        $this->assertTrue($schedule->next_run_at->isAfter($dueAt));
        $this->assertNull($schedule->last_sent_for_date);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'direction' => 'outbound',
            'subject' => 'Weightsy reminder not sent',
            'parsed_status' => 'skipped',
        ]);

        $failed = Message::query()->where('subject', 'Weightsy reminder not sent')->first();
        $this->assertSame('reminder_failed', data_get($failed?->metadata, 'category'));
        $this->assertSame('no_recipient', data_get($failed?->metadata, 'failure_reason'));
    }
}
