<?php

namespace Tests\Feature;

use App\Models\Checkin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundCheckinWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_webhook_accepts_post_without_csrf_token(): void
    {
        $response = $this->postJson('/app/inbound/checkins', [
            'from' => 'webhook@example.com',
            'text' => '180',
            'external_id' => 'msg-unique-1',
            'provider' => 'webhook_test',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('checkins', [
            'metric_type' => 'weight',
        ]);
    }

    public function test_duplicate_external_id_returns_duplicate_without_second_checkin(): void
    {
        $this->postJson('/app/inbound/checkins', [
            'from' => 'dup@example.com',
            'text' => '180',
            'external_id' => 'same-id',
            'provider' => 'test',
        ])->assertSuccessful()
            ->assertJsonPath('duplicate', false);

        $this->assertSame(1, Checkin::query()->count());

        $this->postJson('/app/inbound/checkins', [
            'from' => 'dup@example.com',
            'text' => '181',
            'external_id' => 'same-id',
            'provider' => 'test',
        ])->assertSuccessful()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, Checkin::query()->count());
    }
}
