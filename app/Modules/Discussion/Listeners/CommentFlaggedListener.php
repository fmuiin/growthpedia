<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Listeners;

use App\Modules\Discussion\Events\CommentFlagged;
use App\Modules\Discussion\Models\Comment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommentFlaggedListener
{
    public function handle(CommentFlagged $event): void
    {
        $comment = Comment::with('user')->find($event->commentId);

        if ($comment === null) {
            Log::warning('CommentFlaggedListener: Comment not found', [
                'comment_id' => $event->commentId,
            ]);

            return;
        }

        $author = $comment->user;

        if ($author === null) {
            Log::warning('CommentFlaggedListener: Comment author not found', [
                'comment_id' => $event->commentId,
            ]);

            return;
        }

        Mail::raw(
            "Dear {$author->name},\n\n"
            . "Your comment has been flagged as inappropriate and hidden from public view.\n\n"
            . "Reason: {$event->reason}\n\n"
            . "If you believe this was a mistake, please contact support.\n\n"
            . "Thank you,\nGrowthPedia Team",
            function ($message) use ($author): void {
                $message->to($author->email)
                    ->subject('Your Comment Has Been Flagged');
            },
        );

        Log::info('Comment flagged notification sent to author', [
            'comment_id' => $event->commentId,
            'author_id' => $author->id,
            'email' => $author->email,
            'reason' => $event->reason,
        ]);
    }
}
