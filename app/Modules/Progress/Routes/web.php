<?php

declare(strict_types=1);

use App\Modules\Progress\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/courses/{course}/dashboard', [ProgressController::class, 'dashboard'])->name('progress.dashboard');
    Route::post('/courses/{course}/resume', [ProgressController::class, 'resume'])->name('progress.resume');
    Route::post('/lessons/{lesson}/complete', [ProgressController::class, 'markComplete'])->name('progress.complete');
});
