<?php

namespace App\Http\Controllers;

use App\Services\MagicLoginLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class MagicLoginController extends Controller
{
    public function __construct(
        private readonly MagicLoginLinkService $magicLoginLinkService,
    ) {
    }

    public function __invoke(string $token): RedirectResponse
    {
        $loginToken = $this->magicLoginLinkService->consume($token);

        abort_if($loginToken === null || $loginToken->user === null, 403);

        Auth::login($loginToken->user, remember: false);
        session()->regenerate();

        return redirect()->route('timeline.show');
    }
}
