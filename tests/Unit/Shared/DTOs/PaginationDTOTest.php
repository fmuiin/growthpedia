<?php

declare(strict_types=1);

use App\Shared\DTOs\PaginationDTO;

it('has sensible defaults', function () {
    $dto = new PaginationDTO();

    expect($dto->page)->toBe(1)
        ->and($dto->perPage)->toBe(15);
});

it('accepts custom values', function () {
    $dto = new PaginationDTO(page: 3, perPage: 25);

    expect($dto->page)->toBe(3)
        ->and($dto->perPage)->toBe(25);
});
