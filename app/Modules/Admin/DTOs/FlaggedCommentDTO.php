<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class FlaggedCommentDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $content,
        public string $flagReason,
        public string $authorName,
        public string $lessonTitle,
        public DateTimeInterface $flaggedAt,
    ) {}
}
