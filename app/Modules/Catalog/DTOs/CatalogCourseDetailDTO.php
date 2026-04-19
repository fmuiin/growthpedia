<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CatalogCourseDetailDTO extends BaseDTO
{
    /**
     * @param array<CatalogModuleOutlineDTO> $modules
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $category,
        public string $instructorName,
        public ?string $instructorBio,
        public string $publishedAt,
        public array $modules,
        public int $enrollmentCount,
        public ?float $averageRating,
    ) {}
}
