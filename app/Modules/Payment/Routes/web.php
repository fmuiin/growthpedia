<?php

declare(strict_types=1);

use App\Modules\Payment\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Payment module routes

Route::middleware(['web'])->group(function (): void {
    Route::post('/webhooks/stripe', [WebhookController::class, 'handle'])
        ->name('payment.webhook')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
});
