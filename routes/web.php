<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboundCheckinController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/inbound/checkins', InboundCheckinController::class)->name('inbound.checkins');

    Route::middleware('signed')->group(function () {
        Route::get('/onboarding/confirm/{user}', [OnboardingController::class, 'confirm'])->name('onboarding.confirm');
        Route::get('/onboarding/settings/{user}', [OnboardingController::class, 'edit'])->name('onboarding.edit');
        Route::post('/onboarding/settings/{user}', [OnboardingController::class, 'update'])->name('onboarding.update');
        Route::get('/unsubscribe/{user}', [OnboardingController::class, 'unsubscribe'])->name('onboarding.unsubscribe');
    });
});
