<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CreateModuleDTO extends BaseDTO
{
    public function __construct(
        public int $courseId,
        public string $title,
        public int $sortOrder,
    ) {}
}
