<?php

declare(strict_types=1);

use App\Modules\Admin\Controllers\AdminUserController;
use App\Modules\Admin\Controllers\AnalyticsController;
use App\Modules\Admin\Controllers\MembershipPlanController;
use App\Modules\Admin\Controllers\RefundController;
use Illuminate\Support\Facades\Route;

// Admin module routes
Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function (): void {
    // Membership plan management
    Route::get('/plans', [MembershipPlanController::class, 'index'])->name('admin.plans.index');
    Route::get('/plans/create', [MembershipPlanController::class, 'create'])->name('admin.plans.create');
    Route::post('/plans', [MembershipPlanController::class, 'store'])->name('admin.plans.store');
    Route::get('/plans/{plan}/edit', [MembershipPlanController::class, 'edit'])->name('admin.plans.edit');
    Route::put('/plans/{plan}', [MembershipPlanController::class, 'update'])->name('admin.plans.update');
    Route::post('/plans/{plan}/deactivate', [MembershipPlanController::class, 'deactivate'])->name('admin.plans.deactivate');
    Route::delete('/plans/{plan}', [MembershipPlanController::class, 'destroy'])->name('admin.plans.destroy');

    // Subscription refunds
    Route::post('/subscriptions/{subscription}/refund', [RefundController::class, 'refund'])->name('admin.subscriptions.refund');

    // User management (Requirement 8)
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/search', [AdminUserController::class, 'search'])->name('admin.users.search');
    Route::post('/users/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('admin.users.assign-role');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('admin.users.suspend');

    // Analytics (Requirement 9)
    Route::get('/analytics', [AnalyticsController::class, 'dashboard'])->name('admin.analytics.dashboard');
    Route::get('/analytics/courses/{course}', [AnalyticsController::class, 'courseAnalytics'])->name('admin.analytics.course');
    Route::get('/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('admin.analytics.export');
    Route::get('/analytics/flagged-comments', [AnalyticsController::class, 'flaggedComments'])->name('admin.analytics.flagged-comments');
});
