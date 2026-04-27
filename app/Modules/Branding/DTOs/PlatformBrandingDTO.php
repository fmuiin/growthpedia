<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PlatformBrandingDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $siteName,
        public ?string $tagline,
        public ?string $logoUrl,
        public ?string $faviconUrl,
        public string $primaryColor,
        public string $secondaryColor,
        public ?string $footerText,
    ) {}
}
