<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppSubdirectoryUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_onboarding_links_use_the_app_subdirectory(): void
    {
        config()->set('app.url', 'https://weightsy.com');

        $user = User::factory()->create();

        $confirmUrl = URL::temporarySignedRoute('onboarding.confirm', now()->addHour(), ['user' => $user]);
        $settingsUrl = URL::temporarySignedRoute('onboarding.edit', now()->addHour(), ['user' => $user]);
        $unsubscribeUrl = URL::temporarySignedRoute('onboarding.unsubscribe', now()->addHour(), ['user' => $user]);

        $this->assertStringStartsWith('https://weightsy.com/app/onboarding/confirm/', $confirmUrl);
        $this->assertStringStartsWith('https://weightsy.com/app/onboarding/settings/', $settingsUrl);
        $this->assertStringStartsWith('https://weightsy.com/app/unsubscribe/', $unsubscribeUrl);
    }
}
