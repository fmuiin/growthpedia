<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class CourseAnalyticsDTO extends BaseDTO
{
    public function __construct(
        public int $courseId,
        public string $courseTitle,
        public int $enrollmentCount,
        public string $averageCompletionPercentage,
        public ?string $averageRating,
    ) {}
}
