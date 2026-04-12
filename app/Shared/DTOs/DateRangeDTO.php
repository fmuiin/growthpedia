<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class DateRangeDTO extends BaseDTO
{
    public function __construct(
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
    ) {
        if ($this->startDate->greaterThan($this->endDate)) {
            throw new InvalidArgumentException('Start date must be less than or equal to end date.');
        }
    }
}
