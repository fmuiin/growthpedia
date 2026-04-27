<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class UpdatePlatformBrandingDTO extends BaseDTO
{
    public function __construct(
        public ?string $siteName = null,
        public ?string $tagline = null,
        public ?string $logoUrl = null,
        public ?string $faviconUrl = null,
        public ?string $primaryColor = null,
        public ?string $secondaryColor = null,
        public ?string $footerText = null,
    ) {}
}
