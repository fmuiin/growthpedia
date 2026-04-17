<?php

declare(strict_types=1);

namespace App\Modules\Progress\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class CourseProgressDTO extends BaseDTO
{
    public function __construct(
        public int $enrollmentId,
        public int $courseId,
        public float $completionPercentage,
        public int $completedCount,
        public int $remainingCount,
        public ?DateTimeInterface $completedAt,
    ) {}
}
