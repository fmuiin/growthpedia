<?php

declare(strict_types=1);

namespace App\Modules\Course\Services;

use App\Modules\Course\Contracts\CourseServiceInterface;
use App\Modules\Course\DTOs\CourseDetailDTO;
use App\Modules\Course\DTOs\CourseDTO;
use App\Modules\Course\DTOs\CreateCourseDTO;
use App\Modules\Course\DTOs\CreateLessonDTO;
use App\Modules\Course\DTOs\CreateModuleDTO;
use App\Modules\Course\DTOs\LessonDTO;
use App\Modules\Course\DTOs\ModuleDTO;
use App\Modules\Course\DTOs\UpdateCourseDTO;
use App\Modules\Course\Events\LessonRemovedFromCourse;
use App\Modules\Course\Exceptions\CannotPublishEmptyCourseException;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Carbon;

class CourseService implements CourseServiceInterface
{
    public function createCourse(CreateCourseDTO $dto): CourseDTO
    {
        $course = Course::create([
            'instructor_id' => $dto->instructorId,
            'title' => $dto->title,
            'description' => $dto->description,
            'category' => $dto->category,
            'status' => 'draft',
        ]);

        return $this->toCourseDTO($course);
    }

    public function updateCourse(int $courseId, UpdateCourseDTO $dto): CourseDTO
    {
        $course = Course::find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException("Course not found.");
        }

        $fields = [];

        if ($dto->title !== null) {
            $fields['title'] = $dto->title;
        }
        if ($dto->description !== null) {
            $fields['description'] = $dto->description;
        }
        if ($dto->category !== null) {
            $fields['category'] = $dto->category;
        }

        if ($fields !== []) {
            $course->update($fields);
        }

        return $this->toCourseDTO($course->refresh());
    }

    public function publishCourse(int $courseId): CourseDTO
    {
        $course = Course::find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException("Course not found.");
        }

        if ($course->lessons()->count() === 0) {
            throw new CannotPublishEmptyCourseException();
        }

        $course->update([
            'status' => 'published',
            'published_at' => Carbon::now(),
        ]);

        return $this->toCourseDTO($course->refresh());
    }

    public function unpublishCourse(int $courseId): void
    {
        $course = Course::find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException("Course not found.");
        }

        $course->update([
            'status' => 'unpublished',
        ]);
    }

    public function addModule(int $courseId, CreateModuleDTO $dto): ModuleDTO
    {
        $course = Course::find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException("Course not found.");
        }

        $module = CourseModule::create([
            'course_id' => $courseId,
            'title' => $dto->title,
            'sort_order' => $dto->sortOrder,
        ]);

        return new ModuleDTO(
            id: $module->id,
            courseId: $module->course_id,
            title: $module->title,
            sortOrder: $module->sort_order,
            lessons: [],
        );
    }

    public function addLesson(int $moduleId, CreateLessonDTO $dto): LessonDTO
    {
        $module = CourseModule::find($moduleId);

        if ($module === null) {
            throw new EntityNotFoundException("Module not found.");
        }

        $lesson = Lesson::create([
            'course_module_id' => $moduleId,
            'title' => $dto->title,
            'content_type' => $dto->contentType,
            'content_body' => $dto->contentBody,
            'video_url' => $dto->videoUrl,
            'sort_order' => $dto->sortOrder,
        ]);

        return $this->toLessonDTO($lesson);
    }

    public function getCourseWithStructure(int $courseId): CourseDetailDTO
    {
        $course = Course::with(['instructor', 'modules.lessons'])->find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException("Course not found.");
        }

        $modules = $course->modules
            ->sortBy('sort_order')
            ->values()
            ->map(function (CourseModule $module): ModuleDTO {
                $lessons = $module->lessons
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn (Lesson $lesson): LessonDTO => $this->toLessonDTO($lesson))
                    ->all();

                return new ModuleDTO(
                    id: $module->id,
                    courseId: $module->course_id,
                    title: $module->title,
                    sortOrder: $module->sort_order,
                    lessons: $lessons,
                );
            })
            ->all();

        return new CourseDetailDTO(
            id: $course->id,
            instructorId: $course->instructor_id,
            instructorName: $course->instructor->name,
            title: $course->title,
            description: $course->description,
            category: $course->category,
            status: $course->status,
            publishedAt: $course->published_at,
            modules: $modules,
        );
    }

    public function deleteLessonFromPublishedCourse(int $lessonId): void
    {
        $lesson = Lesson::with('module')->find($lessonId);

        if ($lesson === null) {
            throw new EntityNotFoundException("Lesson not found.");
        }

        $courseId = $lesson->module->course_id;

        $lesson->delete();

        LessonRemovedFromCourse::dispatch($courseId, $lessonId);
    }

    private function toCourseDTO(Course $course): CourseDTO
    {
        return new CourseDTO(
            id: $course->id,
            instructorId: $course->instructor_id,
            title: $course->title,
            description: $course->description,
            category: $course->category,
            status: $course->status,
            publishedAt: $course->published_at,
            createdAt: $course->created_at,
        );
    }

    private function toLessonDTO(Lesson $lesson): LessonDTO
    {
        return new LessonDTO(
            id: $lesson->id,
            courseModuleId: $lesson->course_module_id,
            title: $lesson->title,
            contentType: $lesson->content_type,
            contentBody: $lesson->content_body,
            videoUrl: $lesson->video_url,
            sortOrder: $lesson->sort_order,
        );
    }
}
