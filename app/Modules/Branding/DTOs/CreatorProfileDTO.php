<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CreatorProfileDTO extends BaseDTO
{
    /**
     * @param array<string, string>|null $socialLinks
     * @param array<int>|null $featuredCourseIds
     */
    public function __construct(
        public int $id,
        public int $userId,
        public string $displayName,
        public ?string $bio,
        public ?string $avatarUrl,
        public ?string $expertise,
        public ?array $socialLinks,
        public ?array $featuredCourseIds,
    ) {}
}
