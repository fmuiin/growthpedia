<?php

declare(strict_types=1);

namespace App\Modules\Branding\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branding\Contracts\BrandingServiceInterface;
use App\Modules\Branding\DTOs\UpdateCreatorProfileDTO;
use App\Modules\Branding\DTOs\UpdateLandingPageDTO;
use App\Modules\Branding\DTOs\UpdatePlatformBrandingDTO;
use App\Modules\Branding\Models\LandingPageSection;
use App\Modules\Branding\Requests\UpdateCreatorProfileRequest;
use App\Modules\Branding\Requests\UpdateLandingPageSectionRequest;
use App\Modules\Branding\Requests\UpdatePlatformBrandingRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function __construct(
        private readonly BrandingServiceInterface $brandingService,
    ) {}

    /**
     * Show the creator profile edit page.
     */
    public function showProfile(): Response
    {
        $profile = $this->brandingService->getCreatorProfile();

        return Inertia::render('Admin/CreatorProfileEdit', [
            'profile' => $profile,
        ]);
    }

    /**
     * Update the creator profile.
     */
    public function updateProfile(UpdateCreatorProfileRequest $request): RedirectResponse
    {
        $dto = new UpdateCreatorProfileDTO(
            displayName: $request->validated('display_name'),
            bio: $request->validated('bio'),
            avatarUrl: $request->validated('avatar_url'),
            expertise: $request->validated('expertise'),
            socialLinks: $request->validated('social_links'),
            featuredCourseIds: $request->validated('featured_course_ids'),
        );

        $this->brandingService->updateCreatorProfile($dto);

        return redirect()->back()->with('success', 'Creator profile updated successfully.');
    }

    /**
     * Show the landing page editor.
     */
    public function showLandingEditor(): Response
    {
        $sections = LandingPageSection::query()
            ->orderBy('sort_order', 'asc')
            ->get();

        return Inertia::render('Admin/LandingPageEditor', [
            'sections' => $sections,
        ]);
    }

    /**
     * Create a new landing page section.
     */
    public function createLandingSection(UpdateLandingPageSectionRequest $request): RedirectResponse
    {
        $dto = new UpdateLandingPageDTO(
            sections: [$request->validated()],
        );

        $this->brandingService->updateLandingPageContent($dto);

        return redirect()->back()->with('success', 'Landing page section created successfully.');
    }

    /**
     * Update an existing landing page section.
     */
    public function updateLandingSection(int $sectionId, UpdateLandingPageSectionRequest $request): RedirectResponse
    {
        $section = LandingPageSection::query()->find($sectionId);

        if ($section === null) {
            return redirect()->back()->with('error', 'Landing page section not found.');
        }

        $sectionData = array_merge($request->validated(), ['id' => $sectionId]);

        $dto = new UpdateLandingPageDTO(
            sections: [$sectionData],
        );

        $this->brandingService->updateLandingPageContent($dto);

        return redirect()->back()->with('success', 'Landing page section updated successfully.');
    }

    /**
     * Delete a landing page section.
     */
    public function deleteLandingSection(int $sectionId): RedirectResponse
    {
        $section = LandingPageSection::query()->find($sectionId);

        if ($section === null) {
            return redirect()->back()->with('error', 'Landing page section not found.');
        }

        $section->delete();

        return redirect()->back()->with('success', 'Landing page section deleted successfully.');
    }

    /**
     * Show the platform branding settings page.
     */
    public function showPlatformBranding(): Response
    {
        $branding = $this->brandingService->getPlatformBranding();

        return Inertia::render('Admin/PlatformBrandingEdit', [
            'branding' => $branding,
        ]);
    }

    /**
     * Update platform branding settings.
     */
    public function updatePlatformBranding(UpdatePlatformBrandingRequest $request): RedirectResponse
    {
        $dto = new UpdatePlatformBrandingDTO(
            siteName: $request->validated('site_name'),
            tagline: $request->validated('tagline'),
            logoUrl: $request->validated('logo_url'),
            faviconUrl: $request->validated('favicon_url'),
            primaryColor: $request->validated('primary_color'),
            secondaryColor: $request->validated('secondary_color'),
            footerText: $request->validated('footer_text'),
        );

        $this->brandingService->updatePlatformBranding($dto);

        return redirect()->back()->with('success', 'Platform branding updated successfully.');
    }
}
