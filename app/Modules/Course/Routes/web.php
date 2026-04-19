<?php

declare(strict_types=1);

use App\Modules\Course\Controllers\CourseController;
use App\Modules\Course\Controllers\LessonController;
use App\Modules\Course\Controllers\ModuleController;
use Illuminate\Support\Facades\Route;

// Instructor/Admin course management routes
Route::middleware(['web', 'auth', 'role:instructor,admin'])->group(function (): void {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::post('/courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
    Route::post('/courses/{course}/unpublish', [CourseController::class, 'unpublish'])->name('courses.unpublish');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::post('/courses/{course}/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');

    Route::post('/modules/{module}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
});

// Learner-accessible routes (any authenticated user with active subscription)
Route::middleware(['web', 'auth', 'subscription'])->group(function (): void {
    Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
});
