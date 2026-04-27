<?php

declare(strict_types=1);

namespace App\Modules\Branding\Services;

use App\Models\User;
use App\Modules\Branding\Contracts\BrandingServiceInterface;
use App\Modules\Branding\DTOs\CreatorProfileDTO;
use App\Modules\Branding\DTOs\LandingPageDTO;
use App\Modules\Branding\DTOs\LandingPageSectionDTO;
use App\Modules\Branding\DTOs\PlatformBrandingDTO;
use App\Modules\Branding\DTOs\UpdateCreatorProfileDTO;
use App\Modules\Branding\DTOs\UpdateLandingPageDTO;
use App\Modules\Branding\DTOs\UpdatePlatformBrandingDTO;
use App\Modules\Branding\Models\CreatorProfile;
use App\Modules\Branding\Models\LandingPageSection;
use App\Modules\Branding\Models\PlatformBranding;
use App\Modules\Course\Models\Course;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BrandingService implements BrandingServiceInterface
{
    private const int CACHE_TTL_SECONDS = 300;

    private const string CACHE_KEY_PROFILE = 'branding:profile';

    private const string CACHE_KEY_LANDING = 'branding:landing';

    private const string CACHE_KEY_PLATFORM = 'branding:platform';

    public function getCreatorProfile(): CreatorProfileDTO
    {
        return Cache::remember(self::CACHE_KEY_PROFILE, self::CACHE_TTL_SECONDS, function (): CreatorProfileDTO {
            $profile = CreatorProfile::query()->first();

            if ($profile !== null) {
                return $this->toCreatorProfileDTO($profile);
            }

            $adminUser = User::query()->where('role', 'admin')->first();

            if ($adminUser === null) {
                throw new EntityNotFoundException('No admin user found. Please create an admin account first.');
            }

            $profile = CreatorProfile::create([
                'user_id' => $adminUser->id,
                'display_name' => $adminUser->name,
            ]);

            return $this->toCreatorProfileDTO($profile);
        });
    }

    public function updateCreatorProfile(UpdateCreatorProfileDTO $dto): CreatorProfileDTO
    {
        // Ensure profile exists (auto-initializes if needed)
        $this->getCreatorProfile();

        $profile = CreatorProfile::query()->firstOrFail();

        if ($dto->featuredCourseIds !== null && count($dto->featuredCourseIds) > 0) {
            $publishedCount = Course::query()
                ->whereIn('id', $dto->featuredCourseIds)
                ->where('status', 'published')
                ->count();

            if ($publishedCount !== count($dto->featuredCourseIds)) {
                throw ValidationException::withMessages([
                    'featured_course_ids' => ['Featured courses must be published.'],
                ]);
            }
        }

        $updateData = array_filter([
            'display_name' => $dto->displayName,
            'bio' => $dto->bio,
            'avatar_url' => $dto->avatarUrl,
            'expertise' => $dto->expertise,
            'social_links' => $dto->socialLinks,
            'featured_course_ids' => $dto->featuredCourseIds,
        ], fn ($value) => $value !== null);

        if (! empty($updateData)) {
            $profile->update($updateData);
        }

        Cache::forget(self::CACHE_KEY_PROFILE);
        Cache::forget(self::CACHE_KEY_LANDING);

        return $this->toCreatorProfileDTO($profile->refresh());
    }

    public function getLandingPageContent(): LandingPageDTO
    {
        return Cache::remember(self::CACHE_KEY_LANDING, self::CACHE_TTL_SECONDS, function (): LandingPageDTO {
            $branding = $this->getPlatformBranding();

            $sections = LandingPageSection::query()
                ->where('is_visible', true)
                ->orderBy('sort_order', 'asc')
                ->get()
                ->map(fn (LandingPageSection $section): LandingPageSectionDTO => $this->toLandingPageSectionDTO($section))
                ->all();

            $creatorProfile = CreatorProfile::query()->first();
            $creatorProfileDTO = $creatorProfile !== null ? $this->toCreatorProfileDTO($creatorProfile) : null;

            $featuredCourses = $this->getFeaturedCourses($creatorProfile);

            return new LandingPageDTO(
                branding: $branding,
                sections: $sections,
                creatorProfile: $creatorProfileDTO,
                featuredCourses: $featuredCourses,
            );
        });
    }

    public function updateLandingPageContent(UpdateLandingPageDTO $dto): LandingPageDTO
    {
        foreach ($dto->sections as $sectionData) {
            if (isset($sectionData['id'])) {
                $section = LandingPageSection::query()->find($sectionData['id']);
                if ($section !== null) {
                    $section->update($sectionData);
                }
            } else {
                LandingPageSection::create($sectionData);
            }
        }

        Cache::forget(self::CACHE_KEY_LANDING);

        return $this->getLandingPageContent();
    }

    public function getPlatformBranding(): PlatformBrandingDTO
    {
        return Cache::remember(self::CACHE_KEY_PLATFORM, self::CACHE_TTL_SECONDS, function (): PlatformBrandingDTO {
            $branding = PlatformBranding::query()->first();

            if ($branding !== null) {
                return $this->toPlatformBrandingDTO($branding);
            }

            return new PlatformBrandingDTO(
                id: 0,
                siteName: 'GrowthPedia',
                tagline: null,
                logoUrl: null,
                faviconUrl: null,
                primaryColor: '#3B82F6',
                secondaryColor: '#1E40AF',
                footerText: null,
            );
        });
    }

    public function updatePlatformBranding(UpdatePlatformBrandingDTO $dto): PlatformBrandingDTO
    {
        $branding = PlatformBranding::query()->first();

        $updateData = array_filter([
            'site_name' => $dto->siteName,
            'tagline' => $dto->tagline,
            'logo_url' => $dto->logoUrl,
            'favicon_url' => $dto->faviconUrl,
            'primary_color' => $dto->primaryColor,
            'secondary_color' => $dto->secondaryColor,
            'footer_text' => $dto->footerText,
        ], fn ($value) => $value !== null);

        if ($branding !== null) {
            $branding->update($updateData);
            $branding->refresh();
        } else {
            $branding = PlatformBranding::create(array_merge([
                'site_name' => 'GrowthPedia',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#1E40AF',
            ], $updateData));
        }

        Cache::forget(self::CACHE_KEY_PLATFORM);
        Cache::forget(self::CACHE_KEY_LANDING);

        return $this->toPlatformBrandingDTO($branding);
    }

    public function getCreatorName(): string
    {
        $profile = $this->getCreatorProfile();

        return $profile->displayName;
    }

    private function toCreatorProfileDTO(CreatorProfile $profile): CreatorProfileDTO
    {
        return new CreatorProfileDTO(
            id: $profile->id,
            userId: $profile->user_id,
            displayName: $profile->display_name,
            bio: $profile->bio,
            avatarUrl: $profile->avatar_url,
            expertise: $profile->expertise,
            socialLinks: $profile->social_links,
            featuredCourseIds: $profile->featured_course_ids,
        );
    }

    private function toLandingPageSectionDTO(LandingPageSection $section): LandingPageSectionDTO
    {
        return new LandingPageSectionDTO(
            id: $section->id,
            sectionType: $section->section_type,
            title: $section->title,
            subtitle: $section->subtitle,
            content: $section->content,
            imageUrl: $section->image_url,
            ctaText: $section->cta_text,
            ctaUrl: $section->cta_url,
            sortOrder: $section->sort_order,
            isVisible: $section->is_visible,
            metadata: $section->metadata,
        );
    }

    private function toPlatformBrandingDTO(PlatformBranding $branding): PlatformBrandingDTO
    {
        return new PlatformBrandingDTO(
            id: $branding->id,
            siteName: $branding->site_name,
            tagline: $branding->tagline,
            logoUrl: $branding->logo_url,
            faviconUrl: $branding->favicon_url,
            primaryColor: $branding->primary_color,
            secondaryColor: $branding->secondary_color,
            footerText: $branding->footer_text,
        );
    }

    /**
     * @return array<mixed>
     */
    private function getFeaturedCourses(?CreatorProfile $creatorProfile): array
    {
        if ($creatorProfile !== null && ! empty($creatorProfile->featured_course_ids)) {
            return Course::query()
                ->whereIn('id', $creatorProfile->featured_course_ids)
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->get()
                ->toArray();
        }

        return Course::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get()
            ->toArray();
    }
}
