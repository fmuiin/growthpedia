<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Contracts;

use App\Modules\Discussion\DTOs\CommentDTO;
use App\Modules\Discussion\DTOs\PaginatedCommentsDTO;
use App\Shared\Contracts\ServiceInterface;

interface DiscussionServiceInterface extends ServiceInterface
{
    public function createComment(int $userId, int $lessonId, string $content): CommentDTO;

    public function replyToComment(int $userId, int $parentCommentId, string $content): CommentDTO;

    public function editComment(int $userId, int $commentId, string $newContent): CommentDTO;

    public function flagComment(int $flaggedBy, int $commentId, string $reason): void;

    public function getThreadForLesson(int $lessonId, int $page): PaginatedCommentsDTO;
}
