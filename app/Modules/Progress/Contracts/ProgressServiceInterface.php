<?php

declare(strict_types=1);

namespace App\Modules\Progress\Contracts;

use App\Modules\Course\DTOs\LessonDTO;
use App\Modules\Progress\DTOs\CourseProgressDTO;
use App\Modules\Progress\DTOs\ProgressDTO;
use App\Shared\Contracts\ServiceInterface;

interface ProgressServiceInterface extends ServiceInterface
{
    public function markLessonComplete(int $userId, int $lessonId): ProgressDTO;

    public function getCourseProgress(int $userId, int $courseId): CourseProgressDTO;

    public function getNextLesson(int $userId, int $courseId): ?LessonDTO;

    public function recalculateForCourse(int $courseId): void;
}
