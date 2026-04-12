<?php

declare(strict_types=1);

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Course\Contracts\CourseServiceInterface;
use App\Modules\Course\DTOs\CreateLessonDTO;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Course\Requests\CreateLessonRequest;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
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

    public function show(int $lessonId): Response|RedirectResponse
    {
        $lesson = Lesson::with('module.course')->find($lessonId);

        if ($lesson === null) {
            return redirect()->back()->with('error', 'Lesson not found.');
        }

        return Inertia::render('Course/LessonView', [
            'lesson' => $lesson,
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
}
