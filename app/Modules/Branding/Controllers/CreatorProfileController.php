<?php

declare(strict_types=1);

namespace App\Modules\Branding\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branding\Contracts\BrandingServiceInterface;
use App\Modules\Course\Models\Course;
use Inertia\Inertia;
use Inertia\Response;

class CreatorProfileController extends Controller
{
    public function __construct(
        private readonly BrandingServiceInterface $brandingService,
    ) {}

    /**
     * Show the public creator profile page.
     */
    public function show(): Response
    {
        $profile = $this->brandingService->getCreatorProfile();

        $featuredCourses = [];

        if (! empty($profile->featuredCourseIds)) {
            $featuredCourses = Course::query()
                ->whereIn('id', $profile->featuredCourseIds)
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->get();
        }

        return Inertia::render('CreatorProfile', [
            'profile' => $profile,
            'featuredCourses' => $featuredCourses,
        ]);
    }
}
