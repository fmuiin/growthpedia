<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaginatedCoursesDTO extends BaseDTO
{
    /**
     * @param array<CatalogCourseDTO> $data
     */
    public function __construct(
        public array $data,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}
}
