<?php

declare(strict_types=1);

use App\Modules\Branding\Controllers\BrandingController;
use App\Modules\Branding\Controllers\CreatorProfileController;
use Illuminate\Support\Facades\Route;

// Admin branding routes — behind auth + admin middleware
Route::middleware(['web', 'auth', 'admin'])->prefix('admin/branding')->group(function (): void {
    // Creator profile management
    Route::get('/profile', [BrandingController::class, 'showProfile'])->name('admin.branding.profile');
    Route::put('/profile', [BrandingController::class, 'updateProfile'])->name('admin.branding.profile.update');

    // Landing page section management
    Route::get('/landing-sections', [BrandingController::class, 'showLandingEditor'])->name('admin.branding.landing-sections');
    Route::post('/landing-sections', [BrandingController::class, 'createLandingSection'])->name('admin.branding.landing-sections.store');
    Route::put('/landing-sections/{section}', [BrandingController::class, 'updateLandingSection'])->name('admin.branding.landing-sections.update');
    Route::delete('/landing-sections/{section}', [BrandingController::class, 'deleteLandingSection'])->name('admin.branding.landing-sections.destroy');

    // Platform branding settings
    Route::get('/platform', [BrandingController::class, 'showPlatformBranding'])->name('admin.branding.platform');
    Route::put('/platform', [BrandingController::class, 'updatePlatformBranding'])->name('admin.branding.platform.update');
});

// Public creator profile page — accessible without authentication
Route::middleware(['web'])->group(function (): void {
    Route::get('/creator', [CreatorProfileController::class, 'show'])->name('creator.profile');
});
