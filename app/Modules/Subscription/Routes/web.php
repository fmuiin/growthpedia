<?php

declare(strict_types=1);

use App\Modules\Subscription\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Public route — browse available plans
Route::middleware(['web'])->group(function (): void {
    Route::get('/plans', [SubscriptionController::class, 'plans'])->name('subscription.plans');
});

// Authenticated learner routes
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::get('/subscription/manage', [SubscriptionController::class, 'manage'])->name('subscription.manage');
    Route::post('/subscription/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscription.changePlan');
});
