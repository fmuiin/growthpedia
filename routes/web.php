<?php

use App\Modules\Catalog\Contracts\CatalogServiceInterface;
use App\Modules\Subscription\Models\MembershipPlan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $browse = app(CatalogServiceInterface::class)->browse(1);
    $featuredCourses = collect($browse->data)
        ->take(6)
        ->map(fn ($dto) => $dto->toArray())
        ->values()
        ->all();

    $plans = MembershipPlan::query()
        ->where('is_active', true)
        ->orderBy('price')
        ->limit(3)
        ->get()
        ->map(fn (MembershipPlan $plan): array => [
            'id' => $plan->id,
            'name' => $plan->name,
            'description' => $plan->description,
            'price' => (string) $plan->price,
            'billingFrequency' => $plan->billing_frequency,
            'isActive' => $plan->is_active,
        ])
        ->all();

    return Inertia::render('Landing', [
        'featuredCourses' => $featuredCourses,
        'plans' => $plans,
    ]);
})->name('home');
