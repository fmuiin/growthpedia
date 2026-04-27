<?php

declare(strict_types=1);

namespace App\Modules\Branding\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branding\Contracts\BrandingServiceInterface;
use App\Modules\Subscription\Models\MembershipPlan;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __construct(
        private readonly BrandingServiceInterface $brandingService,
    ) {}

    /**
     * Assemble landing page data and render the dynamic landing page.
     */
    public function index(): Response
    {
        $landingPageDTO = $this->brandingService->getLandingPageContent();

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
            'branding' => $landingPageDTO->branding->toArray(),
            'sections' => array_map(
                fn ($section) => $section->toArray(),
                $landingPageDTO->sections,
            ),
            'creatorProfile' => $landingPageDTO->creatorProfile?->toArray(),
            'featuredCourses' => $landingPageDTO->featuredCourses,
            'plans' => $plans,
        ]);
    }
}
