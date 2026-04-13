<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CreateMembershipPlanDTO extends BaseDTO
{
    /**
     * @param array<int> $courseIds
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public string $price,
        public string $billingFrequency,
        public array $courseIds = [],
    ) {}
}
