<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboundCheckinController;
use App\Http\Controllers\MagicLoginController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TimelineCheckinController;
use App\Http\Controllers\TimelineController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/inbound/checkins', InboundCheckinController::class)->name('inbound.checkins');
    Route::get('/login/{token}', MagicLoginController::class)->name('magic-login.consume');

    Route::middleware('signed')->group(function () {
        Route::get('/onboarding/confirm/{user}', [OnboardingController::class, 'confirm'])->name('onboarding.confirm');
        Route::get('/onboarding/settings/{user}', [OnboardingController::class, 'edit'])->name('onboarding.edit');
        Route::post('/onboarding/settings/{user}', [OnboardingController::class, 'update'])->name('onboarding.update');
        Route::get('/unsubscribe/{user}', [OnboardingController::class, 'unsubscribe'])->name('onboarding.unsubscribe');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/timeline', TimelineController::class)->name('timeline.show');
        Route::post('/timeline/checkins', [TimelineCheckinController::class, 'store'])->name('timeline.checkins.store');
        Route::patch('/timeline/checkins/{checkin}', [TimelineCheckinController::class, 'update'])->name('timeline.checkins.update');
    });
});
