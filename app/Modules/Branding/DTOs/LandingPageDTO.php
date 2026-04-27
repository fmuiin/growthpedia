<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class LandingPageDTO extends BaseDTO
{
    /**
     * @param PlatformBrandingDTO $branding
     * @param array<LandingPageSectionDTO> $sections
     * @param CreatorProfileDTO|null $creatorProfile
     * @param array<mixed> $featuredCourses
     */
    public function __construct(
        public PlatformBrandingDTO $branding,
        public array $sections,
        public ?CreatorProfileDTO $creatorProfile,
        public array $featuredCourses,
    ) {}
}
