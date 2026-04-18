<?php

declare(strict_types=1);

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Course\Contracts\CourseServiceInterface;
use App\Modules\Course\DTOs\CreateLessonDTO;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Requests\CreateLessonRequest;
use App\Modules\Discussion\Contracts\DiscussionServiceInterface;
use App\Modules\Discussion\DTOs\CommentDTO;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function __construct(
        private readonly CourseServiceInterface $courseService,
    ) {}

    public function store(int $moduleId, CreateLessonRequest $request): RedirectResponse
    {
        $module = CourseModule::with('course')->find($moduleId);

        if ($module === null) {
            return redirect()->back()->with('error', 'Module not found.');
        }

        Gate::authorize('update', $module->course);

        try {
            $dto = new CreateLessonDTO(
                courseModuleId: $moduleId,
                title: $request->validated('title'),
                contentType: $request->validated('content_type'),
                contentBody: $request->validated('content_body'),
                videoUrl: $request->validated('video_url'),
                sortOrder: (int) $request->validated('sort_order'),
            );

            $this->courseService->addLesson($moduleId, $dto);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Lesson added successfully.');
    }

    public function show(Request $request, int $lessonId): Response|RedirectResponse
    {
        $lesson = Lesson::with('module.course')->find($lessonId);

        if ($lesson === null) {
            return redirect()->back()->with('error', 'Lesson not found.');
        }

        $discussionService = app(DiscussionServiceInterface::class);
        $paginatedComments = $discussionService->getThreadForLesson($lesson->id, (int) $request->query('comment_page', 1));

        $canComment = false;
        if (Auth::check()) {
            $user = Auth::user();
            $canComment = in_array($user->role, ['instructor', 'admin'])
                || app(SubscriptionServiceInterface::class)->hasActiveSubscription($user->id);
        }

        return Inertia::render('Course/LessonView', [
            'lesson' => $lesson,
            'comments' => [
                'comments' => array_map(fn ($c) => $this->serializeComment($c), $paginatedComments->comments),
                'currentPage' => $paginatedComments->currentPage,
                'lastPage' => $paginatedComments->lastPage,
                'perPage' => $paginatedComments->perPage,
                'total' => $paginatedComments->total,
            ],
            'canComment' => $canComment,
        ]);
    }

    public function destroy(int $lessonId): RedirectResponse
    {
        $lesson = Lesson::with('module.course')->find($lessonId);

        if ($lesson === null) {
            return redirect()->back()->with('error', 'Lesson not found.');
        }

        Gate::authorize('update', $lesson->module->course);

        $course = $lesson->module->course;

        if ($course->status === 'published') {
            try {
                $this->courseService->deleteLessonFromPublishedCourse($lessonId);
            } catch (EntityNotFoundException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            $lesson->delete();
        }

        return redirect()->back()->with('success', 'Lesson deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(CommentDTO $comment): array
    {
        return [
            'id' => $comment->id,
            'lessonId' => $comment->lessonId,
            'userId' => $comment->userId,
            'parentCommentId' => $comment->parentCommentId,
            'content' => $comment->content,
            'isFlagged' => $comment->isFlagged,
            'flagReason' => $comment->flagReason,
            'flaggedBy' => $comment->flaggedBy,
            'isEdited' => $comment->isEdited,
            'editedAt' => $comment->editedAt?->toIso8601String(),
            'createdAt' => $comment->createdAt->toIso8601String(),
            'updatedAt' => $comment->updatedAt->toIso8601String(),
            'authorName' => $comment->authorName,
            'replies' => array_map(fn (CommentDTO $r) => $this->serializeComment($r), $comment->replies),
        ];
    }
}
