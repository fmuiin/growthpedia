<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class CourseDetailDTO extends BaseDTO
{
    /**
     * @param array<ModuleDTO> $modules
     */
    public function __construct(
        public int $id,
        public int $instructorId,
        public string $instructorName,
        public string $title,
        public string $description,
        public string $category,
        public string $status,
        public ?DateTimeInterface $publishedAt,
        public array $modules,
    ) {}
}
