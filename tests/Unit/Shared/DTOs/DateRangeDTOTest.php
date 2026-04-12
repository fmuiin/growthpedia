<?php

declare(strict_types=1);

use App\Shared\DTOs\DateRangeDTO;
use Carbon\CarbonImmutable;

it('creates a valid date range', function () {
    $start = CarbonImmutable::parse('2024-01-01');
    $end = CarbonImmutable::parse('2024-12-31');

    $dto = new DateRangeDTO(startDate: $start, endDate: $end);

    expect($dto->startDate)->toBe($start)
        ->and($dto->endDate)->toBe($end);
});

it('allows same start and end date', function () {
    $date = CarbonImmutable::parse('2024-06-15');

    $dto = new DateRangeDTO(startDate: $date, endDate: $date);

    expect($dto->startDate)->toBe($date)
        ->and($dto->endDate)->toBe($date);
});

it('throws when start date is after end date', function () {
    $start = CarbonImmutable::parse('2024-12-31');
    $end = CarbonImmutable::parse('2024-01-01');

    new DateRangeDTO(startDate: $start, endDate: $end);
})->throws(InvalidArgumentException::class, 'Start date must be less than or equal to end date.');
