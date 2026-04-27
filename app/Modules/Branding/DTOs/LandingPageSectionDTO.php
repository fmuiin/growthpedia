<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class LandingPageSectionDTO extends BaseDTO
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public int $id,
        public string $sectionType,
        public ?string $title,
        public ?string $subtitle,
        public ?string $content,
        public ?string $imageUrl,
        public ?string $ctaText,
        public ?string $ctaUrl,
        public int $sortOrder,
        public bool $isVisible,
        public ?array $metadata,
    ) {}
}
