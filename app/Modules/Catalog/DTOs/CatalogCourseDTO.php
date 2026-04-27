<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CatalogCourseDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $descriptionSummary,
        public string $creatorName,
        public string $category,
        public string $publishedAt,
    ) {}
}
