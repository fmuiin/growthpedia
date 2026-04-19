<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CatalogModuleOutlineDTO extends BaseDTO
{
    /**
     * @param array<CatalogLessonOutlineDTO> $lessons
     */
    public function __construct(
        public int $id,
        public string $title,
        public int $sortOrder,
        public array $lessons,
    ) {}
}
