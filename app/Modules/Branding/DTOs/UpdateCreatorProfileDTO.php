<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class UpdateCreatorProfileDTO extends BaseDTO
{
    /**
     * @param array<string, string>|null $socialLinks
     * @param array<int>|null $featuredCourseIds
     */
    public function __construct(
        public ?string $displayName = null,
        public ?string $bio = null,
        public ?string $avatarUrl = null,
        public ?string $expertise = null,
        public ?array $socialLinks = null,
        public ?array $featuredCourseIds = null,
    ) {}
}
