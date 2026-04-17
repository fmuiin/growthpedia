<?php

declare(strict_types=1);

namespace App\Modules\Progress\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Course\Models\Course;
use App\Modules\Progress\Contracts\ProgressServiceInterface;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function __construct(
        private readonly ProgressServiceInterface $progressService,
    ) {}

    public function dashboard(int $courseId): Response|RedirectResponse
    {
        $user = Auth::user();
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->back()->with('error', 'Course not found.');
        }

        try {
            $progress = $this->progressService->getCourseProgress($user->id, $courseId);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return Inertia::render('Dashboard/LearnerDashboard', [
            'progress' => $progress->toArray(),
            'course' => $course,
        ]);
    }

    public function resume(int $courseId): RedirectResponse
    {
        $user = Auth::user();

        try {
            $nextLesson = $this->progressService->getNextLesson($user->id, $courseId);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($nextLesson === null) {
            return redirect()->back()->with('success', 'You have completed all lessons in this course.');
        }

        return redirect()->route('lessons.show', $nextLesson->id);
    }

    public function markComplete(int $lessonId): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->progressService->markLessonComplete($user->id, $lessonId);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Lesson marked as complete.');
    }
}
