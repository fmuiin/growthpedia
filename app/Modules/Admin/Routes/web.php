<?php

declare(strict_types=1);

use App\Modules\Admin\Controllers\MembershipPlanController;
use Illuminate\Support\Facades\Route;

// Admin module routes
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/plans', [MembershipPlanController::class, 'index'])->name('admin.plans.index');
    Route::get('/plans/create', [MembershipPlanController::class, 'create'])->name('admin.plans.create');
    Route::post('/plans', [MembershipPlanController::class, 'store'])->name('admin.plans.store');
    Route::get('/plans/{plan}/edit', [MembershipPlanController::class, 'edit'])->name('admin.plans.edit');
    Route::put('/plans/{plan}', [MembershipPlanController::class, 'update'])->name('admin.plans.update');
    Route::post('/plans/{plan}/deactivate', [MembershipPlanController::class, 'deactivate'])->name('admin.plans.deactivate');
    Route::delete('/plans/{plan}', [MembershipPlanController::class, 'destroy'])->name('admin.plans.destroy');
});
