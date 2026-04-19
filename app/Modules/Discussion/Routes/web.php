<?php

declare(strict_types=1);

use App\Modules\Discussion\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// Comment creation and editing requires active subscription
Route::middleware(['web', 'auth', 'subscription'])->group(function () {
    Route::post('/lessons/{lessonId}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comments/{commentId}/reply', [CommentController::class, 'reply'])->name('comments.reply');
    Route::put('/comments/{commentId}', [CommentController::class, 'update'])->name('comments.update');
});

// Flagging is restricted to instructors and admins
Route::middleware(['web', 'auth', 'role:instructor,admin'])->group(function () {
    Route::post('/comments/{commentId}/flag', [CommentController::class, 'flag'])->name('comments.flag');
});
