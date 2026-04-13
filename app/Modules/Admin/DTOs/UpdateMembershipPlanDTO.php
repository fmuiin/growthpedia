<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class UpdateMembershipPlanDTO extends BaseDTO
{
    /**
     * @param array<int>|null $courseIds
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $price = null,
        public ?string $billingFrequency = null,
        public ?array $courseIds = null,
    ) {}
}
