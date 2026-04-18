<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Services;

use App\Models\User;
use App\Modules\Discussion\Contracts\DiscussionServiceInterface;
use App\Modules\Discussion\DTOs\CommentDTO;
use App\Modules\Discussion\DTOs\PaginatedCommentsDTO;
use App\Modules\Discussion\Events\CommentFlagged;
use App\Modules\Discussion\Exceptions\CommentingNotAllowedException;
use App\Modules\Discussion\Exceptions\UnauthorizedCommentEditException;
use App\Modules\Discussion\Models\Comment;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Carbon;

class DiscussionService implements DiscussionServiceInterface
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function createComment(int $userId, int $lessonId, string $content): CommentDTO
    {
        $this->ensureCanComment($userId);

        $comment = Comment::create([
            'user_id' => $userId,
            'lesson_id' => $lessonId,
            'content' => $content,
        ]);

        $comment->load('user');

        return $this->toCommentDTO($comment);
    }

    public function replyToComment(int $userId, int $parentCommentId, string $content): CommentDTO
    {
        $this->ensureCanComment($userId);

        $parentComment = Comment::find($parentCommentId);

        if ($parentComment === null) {
            throw new EntityNotFoundException('Parent comment not found.');
        }

        $comment = Comment::create([
            'user_id' => $userId,
            'lesson_id' => $parentComment->lesson_id,
            'parent_comment_id' => $parentComment->id,
            'content' => $content,
        ]);

        $comment->load('user');

        return $this->toCommentDTO($comment);
    }

    public function editComment(int $userId, int $commentId, string $newContent): CommentDTO
    {
        $comment = Comment::find($commentId);

        if ($comment === null) {
            throw new EntityNotFoundException('Comment not found.');
        }

        if ($comment->user_id !== $userId) {
            throw new UnauthorizedCommentEditException();
        }

        $comment->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => Carbon::now(),
        ]);

        $comment->load('user');

        return $this->toCommentDTO($comment);
    }

    public function flagComment(int $flaggedBy, int $commentId, string $reason): void
    {
        $comment = Comment::find($commentId);

        if ($comment === null) {
            throw new EntityNotFoundException('Comment not found.');
        }

        $comment->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
            'flagged_by' => $flaggedBy,
        ]);

        CommentFlagged::dispatch($comment->id, $flaggedBy, $reason);
    }

    public function getThreadForLesson(int $lessonId, int $page): PaginatedCommentsDTO
    {
        $paginator = Comment::where('lesson_id', $lessonId)
            ->whereNull('parent_comment_id')
            ->where('is_flagged', false)
            ->orderBy('created_at', 'asc')
            ->with(['user', 'replies' => function ($query) {
                $query->where('is_flagged', false)
                    ->orderBy('created_at', 'asc');
            }, 'replies.user'])
            ->paginate(perPage: 15, page: $page);

        $comments = $paginator->getCollection()->map(
            fn (Comment $comment) => $this->toCommentDTO($comment),
        )->all();

        return new PaginatedCommentsDTO(
            comments: $comments,
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }

    private function ensureCanComment(int $userId): void
    {
        $user = User::find($userId);

        if ($user === null) {
            throw new EntityNotFoundException('User not found.');
        }

        // Instructors and admins can always comment
        if (!in_array($user->role, ['instructor', 'admin'])) {
            if (!$this->subscriptionService->hasActiveSubscription($userId)) {
                throw new CommentingNotAllowedException();
            }
        }
    }

    private function toCommentDTO(Comment $comment): CommentDTO
    {
        $replies = [];

        if ($comment->relationLoaded('replies')) {
            $replies = $comment->replies->map(
                fn (Comment $reply) => $this->toCommentDTO($reply),
            )->all();
        }

        return new CommentDTO(
            id: $comment->id,
            lessonId: $comment->lesson_id,
            userId: $comment->user_id,
            parentCommentId: $comment->parent_comment_id,
            content: $comment->content,
            isFlagged: $comment->is_flagged ?? false,
            flagReason: $comment->flag_reason,
            flaggedBy: $comment->flagged_by,
            isEdited: $comment->is_edited ?? false,
            editedAt: $comment->edited_at,
            createdAt: $comment->created_at,
            updatedAt: $comment->updated_at,
            authorName: $comment->user->name,
            replies: $replies,
        );
    }
}
