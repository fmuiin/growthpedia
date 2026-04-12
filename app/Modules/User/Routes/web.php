<?php

declare(strict_types=1);

use App\Modules\User\Controllers\ForgotPasswordController;
use App\Modules\User\Controllers\LoginController;
use App\Modules\User\Controllers\RegisterController;
use App\Modules\User\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Guest routes (unauthenticated users only)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});

// Email verification (signed URL, no auth required)
Route::get('/email/verify/{id}', [VerifyEmailController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
