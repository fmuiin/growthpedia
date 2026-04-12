<?php

declare(strict_types=1);

namespace App\Modules\Course\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class LessonDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public int $courseModuleId,
        public string $title,
        public string $contentType,
        public ?string $contentBody,
        public ?string $videoUrl,
        public int $sortOrder,
    ) {}
}
