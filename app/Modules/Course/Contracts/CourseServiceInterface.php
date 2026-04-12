<?php

declare(strict_types=1);

namespace App\Modules\Course\Contracts;

use App\Modules\Course\DTOs\CourseDetailDTO;
use App\Modules\Course\DTOs\CourseDTO;
use App\Modules\Course\DTOs\CreateCourseDTO;
use App\Modules\Course\DTOs\CreateLessonDTO;
use App\Modules\Course\DTOs\CreateModuleDTO;
use App\Modules\Course\DTOs\LessonDTO;
use App\Modules\Course\DTOs\ModuleDTO;
use App\Modules\Course\DTOs\UpdateCourseDTO;
use App\Shared\Contracts\ServiceInterface;

interface CourseServiceInterface extends ServiceInterface
{
    public function createCourse(CreateCourseDTO $dto): CourseDTO;

    public function updateCourse(int $courseId, UpdateCourseDTO $dto): CourseDTO;

    public function publishCourse(int $courseId): CourseDTO;

    public function unpublishCourse(int $courseId): void;

    public function addModule(int $courseId, CreateModuleDTO $dto): ModuleDTO;

    public function addLesson(int $moduleId, CreateLessonDTO $dto): LessonDTO;

    public function getCourseWithStructure(int $courseId): CourseDetailDTO;

    public function deleteLessonFromPublishedCourse(int $lessonId): void;
}
