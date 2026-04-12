<?php

declare(strict_types=1);

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Course\Contracts\CourseServiceInterface;
use App\Modules\Course\DTOs\CreateModuleDTO;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Requests\CreateModuleRequest;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ModuleController extends Controller
{
    public function __construct(
        private readonly CourseServiceInterface $courseService,
    ) {}

    public function store(int $courseId, CreateModuleRequest $request): RedirectResponse
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return redirect()->back()->with('error', 'Course not found.');
        }

        Gate::authorize('update', $course);

        try {
            $dto = new CreateModuleDTO(
                courseId: $courseId,
                title: $request->validated('title'),
                sortOrder: (int) $request->validated('sort_order'),
            );

            $this->courseService->addModule($courseId, $dto);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Module added successfully.');
    }

    public function destroy(int $moduleId): RedirectResponse
    {
        $module = CourseModule::with('course')->find($moduleId);

        if ($module === null) {
            return redirect()->back()->with('error', 'Module not found.');
        }

        Gate::authorize('update', $module->course);

        $module->delete();

        return redirect()->back()->with('success', 'Module deleted successfully.');
    }
}
