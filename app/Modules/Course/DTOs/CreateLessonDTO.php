<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CreateLessonDTO extends BaseDTO
{
    public function __construct(
        public int $courseModuleId,
        public string $title,
        public string $contentType,
        public ?string $contentBody = null,
        public ?string $videoUrl = null,
        public int $sortOrder = 0,
    ) {}
}
