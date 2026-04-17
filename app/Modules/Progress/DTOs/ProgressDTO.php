<?php

declare(strict_types=1);

namespace App\Modules\Progress\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class ProgressDTO extends BaseDTO
{
    public function __construct(
        public int $enrollmentId,
        public int $lessonId,
        public ?DateTimeInterface $completedAt,
    ) {}
}
