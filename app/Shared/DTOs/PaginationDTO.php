<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

final readonly class PaginationDTO extends BaseDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
    ) {}
}
