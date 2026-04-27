<?php

declare(strict_types=1);

namespace App\Modules\Branding\Contracts;

use App\Modules\Branding\DTOs\CreatorProfileDTO;
use App\Modules\Branding\DTOs\LandingPageDTO;
use App\Modules\Branding\DTOs\PlatformBrandingDTO;
use App\Modules\Branding\DTOs\UpdateCreatorProfileDTO;
use App\Modules\Branding\DTOs\UpdateLandingPageDTO;
use App\Modules\Branding\DTOs\UpdatePlatformBrandingDTO;
use App\Shared\Contracts\ServiceInterface;

interface BrandingServiceInterface extends ServiceInterface
{
    public function getCreatorProfile(): CreatorProfileDTO;

    public function updateCreatorProfile(UpdateCreatorProfileDTO $dto): CreatorProfileDTO;

    public function getLandingPageContent(): LandingPageDTO;

    public function updateLandingPageContent(UpdateLandingPageDTO $dto): LandingPageDTO;

    public function getPlatformBranding(): PlatformBrandingDTO;

    public function updatePlatformBranding(UpdatePlatformBrandingDTO $dto): PlatformBrandingDTO;

    public function getCreatorName(): string;
}
