<?php

declare(strict_types=1);

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Course\Contracts\CourseServiceInterface;
use App\Modules\Course\DTOs\CreateCourseDTO;
use App\Modules\Course\DTOs\UpdateCourseDTO;
use App\Modules\Course\Exceptions\CannotPublishEmptyCourseException;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Requests\CreateCourseRequest;
use App\Modules\Course\Requests\UpdateCourseRequest;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseServiceInterface $courseService,
    ) {}

    public function index(): Response
    {
        $courses = Course::orderByDesc('created_at')->get();

        return Inertia::render('Course/CourseList', [
            'courses' => $courses,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Course/CourseCreate');
    }

    public function store(CreateCourseRequest $request): RedirectResponse
    {
        $dto = new CreateCourseDTO(
            title: $request->validated('title'),
            description: $request->validated('description'),
            category: $request->validated('category'),
        );

        $course = $this->courseService->createCourse($dto);

        return redirect()->route('courses.edit', $course->id)
            ->with('success', 'Course created successfully.');
    }

    public function edit(int $courseId): Response|RedirectResponse
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->route('courses.index')
                ->with('error', 'Course not found.');
        }

        Gate::authorize('update', $course);

        $courseDetail = $this->courseService->getCourseWithStructure($courseId);

        return Inertia::render('Course/CourseEdit', [
            'course' => $courseDetail,
        ]);
    }

    public function update(int $courseId, UpdateCourseRequest $request): RedirectResponse
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->route('courses.index')
                ->with('error', 'Course not found.');
        }

        Gate::authorize('update', $course);

        try {
            $dto = new UpdateCourseDTO(
                title: $request->validated('title'),
                description: $request->validated('description'),
                category: $request->validated('category'),
            );

            $this->courseService->updateCourse($courseId, $dto);
        } catch (EntityNotFoundException $e) {
            return redirect()->route('courses.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Course updated successfully.');
    }

    public function publish(int $courseId): RedirectResponse
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->route('courses.index')
                ->with('error', 'Course not found.');
        }

        Gate::authorize('publish', $course);

        try {
            $this->courseService->publishCourse($courseId);
        } catch (CannotPublishEmptyCourseException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Course published successfully.');
    }

    public function unpublish(int $courseId): RedirectResponse
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->route('courses.index')
                ->with('error', 'Course not found.');
        }

        Gate::authorize('publish', $course);

        try {
            $this->courseService->unpublishCourse($courseId);
        } catch (EntityNotFoundException $e) {
            return redirect()->route('courses.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Course unpublished successfully.');
    }

    public function destroy(int $courseId): RedirectResponse
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->route('courses.index')
                ->with('error', 'Course not found.');
        }

        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
