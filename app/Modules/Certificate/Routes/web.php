<?php

declare(strict_types=1);

use App\Modules\Certificate\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
});

// Public route (no auth required)
Route::middleware(['web'])->group(function () {
    Route::get('/verify', [CertificateController::class, 'verify'])->name('certificates.verify');
    Route::post('/verify', [CertificateController::class, 'verifySubmit'])->name('certificates.verify.submit');
});
