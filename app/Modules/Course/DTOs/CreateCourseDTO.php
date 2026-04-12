<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CreateCourseDTO extends BaseDTO
{
    public function __construct(
        public int $instructorId,
        public string $title,
        public string $description,
        public string $category,
    ) {}
}
