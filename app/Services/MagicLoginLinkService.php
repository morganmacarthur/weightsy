<?php

namespace App\Services;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MagicLoginLinkService
{
    public function createForUser(User $user, string $purpose = 'timeline'): string
    {
        $plainToken = Str::random(64);

        LoginToken::query()->create([
            'user_id' => $user->id,
            'channel' => 'email',
            'sent_to' => $user->email ?? $user->contactPoints()->value('address') ?? '',
            'purpose' => $purpose,
            'token_hash' => Hash::make($plainToken),
            'expires_at' => now()->addMinutes(60 * 24 * 14),
        ]);

        return URL::route('magic-login.consume', ['token' => $plainToken]);
    }

    public function consume(string $plainToken, string $purpose = 'timeline'): ?LoginToken
    {
        $token = LoginToken::query()
            ->with('user')
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->get()
            ->first(fn (LoginToken $candidate) => Hash::check($plainToken, $candidate->token_hash));

        if (! $token) {
            return null;
        }

        $token->update([
            'used_at' => now(),
        ]);

        return $token->fresh(['user']);
    }
}
