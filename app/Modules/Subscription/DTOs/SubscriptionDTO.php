<?php

declare(strict_types=1);

namespace App\Modules\Subscription\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class SubscriptionDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $membershipPlanId,
        public string $status,
        public DateTimeInterface $startsAt,
        public DateTimeInterface $endsAt,
        public ?DateTimeInterface $gracePeriodEndsAt,
        public ?DateTimeInterface $cancelledAt,
        public ?string $gatewaySubscriptionId,
        public DateTimeInterface $createdAt,
    ) {}
}
