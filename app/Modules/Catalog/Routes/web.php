<?php

declare(strict_types=1);

use App\Modules\Catalog\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

// Public catalog routes (accessible without authentication)
Route::middleware(['web'])->group(function (): void {
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/search', [CatalogController::class, 'search'])->name('catalog.search');
    Route::get('/catalog/{course}', [CatalogController::class, 'show'])->name('catalog.show');
});
