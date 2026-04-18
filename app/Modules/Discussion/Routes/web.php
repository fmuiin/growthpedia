<?php

declare(strict_types=1);

use App\Modules\Discussion\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/lessons/{lessonId}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comments/{commentId}/reply', [CommentController::class, 'reply'])->name('comments.reply');
    Route::put('/comments/{commentId}', [CommentController::class, 'update'])->name('comments.update');
    Route::post('/comments/{commentId}/flag', [CommentController::class, 'flag'])->name('comments.flag');
});
