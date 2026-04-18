<?php

declare(strict_types=1);

namespace App\Modules\Discussion\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class CommentDTO extends BaseDTO
{
    /**
     * @param array<CommentDTO> $replies
     */
    public function __construct(
        public int $id,
        public int $lessonId,
        public int $userId,
        public ?int $parentCommentId,
        public string $content,
        public bool $isFlagged,
        public ?string $flagReason,
        public ?int $flaggedBy,
        public bool $isEdited,
        public ?DateTimeInterface $editedAt,
        public DateTimeInterface $createdAt,
        public DateTimeInterface $updatedAt,
        public string $authorName,
        public array $replies = [],
    ) {}
}
