<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class ModuleDTO extends BaseDTO
{
    /**
     * @param array<LessonDTO> $lessons
     */
    public function __construct(
        public int $id,
        public int $courseId,
        public string $title,
        public int $sortOrder,
        public array $lessons,
    ) {}
}
