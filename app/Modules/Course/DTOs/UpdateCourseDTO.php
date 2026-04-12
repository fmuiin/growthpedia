<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class UpdateCourseDTO extends BaseDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $category = null,
    ) {}
}
