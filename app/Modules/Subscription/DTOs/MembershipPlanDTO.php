<?php

declare(strict_types=1);

namespace App\Modules\Subscription\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class MembershipPlanDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $price,
        public string $billingFrequency,
        public bool $isActive,
        public DateTimeInterface $createdAt,
    ) {}
}
