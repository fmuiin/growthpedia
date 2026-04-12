<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Unit;

use App\Modules\Course\DTOs\CourseDTO;
use App\Modules\Course\DTOs\CourseDetailDTO;
use App\Modules\Course\DTOs\CreateCourseDTO;
use App\Modules\Course\DTOs\CreateLessonDTO;
use App\Modules\Course\DTOs\CreateModuleDTO;
use App\Modules\Course\DTOs\LessonDTO;
use App\Modules\Course\DTOs\ModuleDTO;
use App\Modules\Course\DTOs\UpdateCourseDTO;
use DateTimeImmutable;
use Tests\TestCase;

class CourseDTOTest extends TestCase
{
    public function test_create_course_dto_construction(): void
    {
        $dto = new CreateCourseDTO(
            instructorId: 1,
            title: 'PHP Basics',
            description: 'Learn PHP',
            category: 'Programming',
        );

        $this->assertEquals(1, $dto->instructorId);
        $this->assertEquals('PHP Basics', $dto->title);
        $this->assertEquals('Learn PHP', $dto->description);
        $this->assertEquals('Programming', $dto->category);
    }

    public function test_create_course_dto_to_array(): void
    {
        $dto = new CreateCourseDTO(
            instructorId: 1,
            title: 'PHP Basics',
            description: 'Learn PHP',
            category: 'Programming',
        );

        $array = $dto->toArray();
        $this->assertEquals([
            'instructorId' => 1,
            'title' => 'PHP Basics',
            'description' => 'Learn PHP',
            'category' => 'Programming',
        ], $array);
    }

    public function test_update_course_dto_with_partial_fields(): void
    {
        $dto = new UpdateCourseDTO(title: 'Updated Title');

        $this->assertEquals('Updated Title', $dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->category);
    }

    public function test_course_dto_construction(): void
    {
        $now = new DateTimeImmutable();
        $dto = new CourseDTO(
            id: 1,
            instructorId: 2,
            title: 'Course',
            description: 'Desc',
            category: 'Cat',
            status: 'draft',
            publishedAt: null,
            createdAt: $now,
        );

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('draft', $dto->status);
        $this->assertNull($dto->publishedAt);
        $this->assertSame($now, $dto->createdAt);
    }

    public function test_course_detail_dto_with_modules(): void
    {
        $lessonDto = new LessonDTO(
            id: 1,
            courseModuleId: 1,
            title: 'Lesson 1',
            contentType: 'text',
            contentBody: 'Content',
            videoUrl: null,
            sortOrder: 1,
        );

        $moduleDto = new ModuleDTO(
            id: 1,
            courseId: 1,
            title: 'Module 1',
            sortOrder: 1,
            lessons: [$lessonDto],
        );

        $dto = new CourseDetailDTO(
            id: 1,
            instructorId: 2,
            instructorName: 'John Doe',
            title: 'Course',
            description: 'Desc',
            category: 'Cat',
            status: 'published',
            publishedAt: new DateTimeImmutable(),
            modules: [$moduleDto],
        );

        $this->assertCount(1, $dto->modules);
        $this->assertEquals('Module 1', $dto->modules[0]->title);
        $this->assertCount(1, $dto->modules[0]->lessons);
        $this->assertEquals('John Doe', $dto->instructorName);
    }

    public function test_create_module_dto_construction(): void
    {
        $dto = new CreateModuleDTO(courseId: 1, title: 'Module 1', sortOrder: 1);

        $this->assertEquals(1, $dto->courseId);
        $this->assertEquals('Module 1', $dto->title);
        $this->assertEquals(1, $dto->sortOrder);
    }

    public function test_create_lesson_dto_construction(): void
    {
        $dto = new CreateLessonDTO(
            courseModuleId: 1,
            title: 'Lesson 1',
            contentType: 'video',
            videoUrl: 'https://example.com/video.mp4',
            sortOrder: 1,
        );

        $this->assertEquals(1, $dto->courseModuleId);
        $this->assertEquals('video', $dto->contentType);
        $this->assertNull($dto->contentBody);
        $this->assertEquals('https://example.com/video.mp4', $dto->videoUrl);
    }

    public function test_lesson_dto_construction(): void
    {
        $dto = new LessonDTO(
            id: 5,
            courseModuleId: 2,
            title: 'Mixed Lesson',
            contentType: 'mixed',
            contentBody: 'Some text',
            videoUrl: 'https://example.com/v.mp4',
            sortOrder: 3,
        );

        $this->assertEquals(5, $dto->id);
        $this->assertEquals('mixed', $dto->contentType);
        $this->assertEquals('Some text', $dto->contentBody);
        $this->assertEquals('https://example.com/v.mp4', $dto->videoUrl);
    }
}
