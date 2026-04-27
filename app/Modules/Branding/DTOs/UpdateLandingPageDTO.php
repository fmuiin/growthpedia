<?php

declare(strict_types=1);

namespace App\Modules\Branding\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class UpdateLandingPageDTO extends BaseDTO
{
    /**
     * @param array<array<string, mixed>> $sections
     */
    public function __construct(
        public array $sections,
    ) {}
}
