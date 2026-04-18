<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discussion\Contracts\DiscussionServiceInterface;
use App\Modules\Discussion\Exceptions\CommentingNotAllowedException;
use App\Modules\Discussion\Exceptions\UnauthorizedCommentEditException;
use App\Modules\Discussion\Requests\CreateCommentRequest;
use App\Modules\Discussion\Requests\EditCommentRequest;
use App\Modules\Discussion\Requests\FlagCommentRequest;
use App\Modules\Discussion\Requests\ReplyCommentRequest;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        private readonly DiscussionServiceInterface $discussionService,
    ) {}

    public function store(CreateCommentRequest $request, int $lessonId): RedirectResponse
    {
        try {
            $this->discussionService->createComment(
                userId: Auth::user()->id,
                lessonId: $lessonId,
                content: $request->validated('content'),
            );
        } catch (CommentingNotAllowedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Comment posted successfully.');
    }

    public function reply(ReplyCommentRequest $request, int $commentId): RedirectResponse
    {
        try {
            $this->discussionService->replyToComment(
                userId: Auth::user()->id,
                parentCommentId: $commentId,
                content: $request->validated('content'),
            );
        } catch (CommentingNotAllowedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Reply posted successfully.');
    }

    public function update(EditCommentRequest $request, int $commentId): RedirectResponse
    {
        try {
            $this->discussionService->editComment(
                userId: Auth::user()->id,
                commentId: $commentId,
                newContent: $request->validated('content'),
            );
        } catch (UnauthorizedCommentEditException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Comment updated successfully.');
    }

    public function flag(FlagCommentRequest $request, int $commentId): RedirectResponse
    {
        try {
            $this->discussionService->flagComment(
                flaggedBy: Auth::user()->id,
                commentId: $commentId,
                reason: $request->validated('reason'),
            );
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Comment flagged successfully.');
    }
}
