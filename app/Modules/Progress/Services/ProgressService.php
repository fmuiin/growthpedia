<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Modules\Course\DTOs\LessonDTO;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\Lesson;
use App\Modules\Progress\Contracts\ProgressServiceInterface;
use App\Modules\Progress\DTOs\CourseProgressDTO;
use App\Modules\Progress\DTOs\ProgressDTO;
use App\Modules\Progress\Events\CourseCompleted;
use App\Modules\Progress\Events\LessonCompleted;
use App\Modules\Progress\Models\Enrollment;
use App\Modules\Progress\Models\LessonProgress;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Carbon;

class ProgressService implements ProgressServiceInterface
{
    public function markLessonComplete(int $userId, int $lessonId): ProgressDTO
    {
        $lesson = Lesson::with('module')->find($lessonId);

        if ($lesson === null) {
            throw new EntityNotFoundException('Lesson not found.');
        }

        $courseId = $lesson->module->course_id;

        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            [
                'enrolled_at' => Carbon::now(),
                'completion_percentage' => 0,
            ],
        );

        $lessonProgress = LessonProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lessonId,
            ],
            [
                'completed_at' => Carbon::now(),
            ],
        );

        $totalLessons = Course::find($courseId)->lessons()->count();
        $completedLessons = $enrollment->lessonProgress()->count();

        $percentage = $totalLessons > 0
            ? ($completedLessons / $totalLessons) * 100
            : 0;

        $enrollment->update([
            'completion_percentage' => $percentage,
        ]);

        if ($percentage >= 100) {
            $enrollment->update([
                'completed_at' => Carbon::now(),
            ]);

            CourseCompleted::dispatch($userId, $courseId, $enrollment->id);
        }

        LessonCompleted::dispatch($userId, $lessonId, $courseId, $enrollment->id);

        return new ProgressDTO(
            enrollmentId: $enrollment->id,
            lessonId: $lessonId,
            completedAt: $lessonProgress->completed_at,
        );
    }

    public function getCourseProgress(int $userId, int $courseId): CourseProgressDTO
    {
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment === null) {
            throw new EntityNotFoundException('Enrollment not found.');
        }

        $totalLessons = Course::find($courseId)->lessons()->count();
        $completedLessons = $enrollment->lessonProgress()->count();
        $remainingLessons = $totalLessons - $completedLessons;

        return new CourseProgressDTO(
            enrollmentId: $enrollment->id,
            courseId: $courseId,
            completionPercentage: (float) $enrollment->completion_percentage,
            completedCount: $completedLessons,
            remainingCount: $remainingLessons,
            completedAt: $enrollment->completed_at,
        );
    }

    public function getNextLesson(int $userId, int $courseId): ?LessonDTO
    {
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment === null) {
            throw new EntityNotFoundException('Enrollment not found.');
        }

        $completedLessonIds = $enrollment->lessonProgress()
            ->pluck('lesson_id')
            ->all();

        $course = Course::with(['modules' => function ($query) {
            $query->orderBy('sort_order');
        }, 'modules.lessons' => function ($query) {
            $query->orderBy('sort_order');
        }])->find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException('Course not found.');
        }

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if (!in_array($lesson->id, $completedLessonIds, true)) {
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
        }

        return null;
    }

    public function recalculateForCourse(int $courseId): void
    {
        $course = Course::find($courseId);

        if ($course === null) {
            return;
        }

        $totalLessons = $course->lessons()->count();

        $enrollments = Enrollment::where('course_id', $courseId)->get();

        foreach ($enrollments as $enrollment) {
            $completedLessons = $enrollment->lessonProgress()
                ->whereHas('lesson')
                ->count();

            $percentage = $totalLessons > 0
                ? ($completedLessons / $totalLessons) * 100
                : 0;

            $updateData = [
                'completion_percentage' => $percentage,
            ];

            if ($percentage >= 100 && $enrollment->completed_at === null) {
                $updateData['completed_at'] = Carbon::now();

                CourseCompleted::dispatch(
                    $enrollment->user_id,
                    $courseId,
                    $enrollment->id,
                );
            }

            if ($percentage < 100 && $enrollment->completed_at !== null) {
                $updateData['completed_at'] = null;
            }

            $enrollment->update($updateData);
        }
    }
}
