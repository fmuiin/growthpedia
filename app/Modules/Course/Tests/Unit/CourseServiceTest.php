<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Unit;

use App\Models\User;
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
use App\Modules\Course\Services\CourseService;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    private CourseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseService();
    }

    public function test_create_course_returns_course_dto_with_draft_status(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Auth::login($user);

        $dto = new CreateCourseDTO(
            title: 'Laravel Basics',
            description: 'Learn Laravel',
            category: 'Programming',
        );

        $result = $this->service->createCourse($dto);

        $this->assertInstanceOf(CourseDTO::class, $result);
        $this->assertEquals($user->id, $result->createdBy);
        $this->assertEquals('Laravel Basics', $result->title);
        $this->assertEquals('Learn Laravel', $result->description);
        $this->assertEquals('Programming', $result->category);
        $this->assertEquals('draft', $result->status);
        $this->assertNull($result->publishedAt);
    }

    public function test_create_course_persists_to_database(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Auth::login($user);

        $dto = new CreateCourseDTO(
            title: 'Test Course',
            description: 'Description',
            category: 'Design',
        );

        $result = $this->service->createCourse($dto);

        $this->assertDatabaseHas('courses', [
            'id' => $result->id,
            'title' => 'Test Course',
            'status' => 'draft',
        ]);
    }

    public function test_update_course_updates_only_non_null_fields(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Original',
            'description' => 'Original Desc',
            'category' => 'Original Cat',
        ]);

        $result = $this->service->updateCourse($course->id, new UpdateCourseDTO(title: 'Updated Title'));

        $this->assertEquals('Updated Title', $result->title);
        $this->assertEquals('Original Desc', $result->description);
        $this->assertEquals('Original Cat', $result->category);
    }

    public function test_update_course_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->updateCourse(999, new UpdateCourseDTO(title: 'X'));
    }

    public function test_add_module_returns_module_dto_with_empty_lessons(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
        ]);

        $dto = new CreateModuleDTO(courseId: $course->id, title: 'Module 1', sortOrder: 1);
        $result = $this->service->addModule($course->id, $dto);

        $this->assertInstanceOf(ModuleDTO::class, $result);
        $this->assertEquals('Module 1', $result->title);
        $this->assertEquals(1, $result->sortOrder);
        $this->assertEquals([], $result->lessons);
    }

    public function test_add_module_throws_when_course_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->addModule(999, new CreateModuleDTO(courseId: 999, title: 'M', sortOrder: 1));
    }

    public function test_add_lesson_returns_lesson_dto(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
        ]);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Module', 'sort_order' => 1]);

        $dto = new CreateLessonDTO(
            courseModuleId: $module->id,
            title: 'Lesson 1',
            contentType: 'text',
            contentBody: 'Hello world',
            sortOrder: 1,
        );
        $result = $this->service->addLesson($module->id, $dto);

        $this->assertInstanceOf(LessonDTO::class, $result);
        $this->assertEquals('Lesson 1', $result->title);
        $this->assertEquals('text', $result->contentType);
        $this->assertEquals('Hello world', $result->contentBody);
    }

    public function test_add_lesson_throws_when_module_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->addLesson(999, new CreateLessonDTO(
            courseModuleId: 999,
            title: 'L',
            contentType: 'text',
        ));
    }

    public function test_publish_course_sets_status_and_published_at(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
        ]);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Module', 'sort_order' => 1]);
        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $result = $this->service->publishCourse($course->id);

        $this->assertEquals('published', $result->status);
        $this->assertNotNull($result->publishedAt);
    }

    public function test_publish_course_with_zero_lessons_throws_exception(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Empty Course',
            'description' => 'Desc',
            'category' => 'Cat',
        ]);

        $this->expectException(CannotPublishEmptyCourseException::class);
        $this->expectExceptionMessage('Course must have at least one lesson to publish');
        $this->service->publishCourse($course->id);
    }

    public function test_publish_course_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->publishCourse(999);
    }

    public function test_unpublish_course_sets_status_to_unpublished(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $this->service->unpublishCourse($course->id);

        $course->refresh();
        $this->assertEquals('unpublished', $course->status);
    }

    public function test_unpublish_course_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->unpublishCourse(999);
    }

    public function test_get_course_with_structure_returns_modules_and_lessons_in_sort_order(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Structured Course',
            'description' => 'Desc',
            'category' => 'Cat',
        ]);

        $module2 = CourseModule::create(['course_id' => $course->id, 'title' => 'Second Module', 'sort_order' => 2]);
        $module1 = CourseModule::create(['course_id' => $course->id, 'title' => 'First Module', 'sort_order' => 1]);

        Lesson::create(['course_module_id' => $module1->id, 'title' => 'Lesson B', 'content_type' => 'text', 'sort_order' => 2]);
        Lesson::create(['course_module_id' => $module1->id, 'title' => 'Lesson A', 'content_type' => 'text', 'sort_order' => 1]);
        Lesson::create(['course_module_id' => $module2->id, 'title' => 'Lesson C', 'content_type' => 'video', 'video_url' => 'https://example.com/v.mp4', 'sort_order' => 1]);

        $result = $this->service->getCourseWithStructure($course->id);

        $this->assertInstanceOf(CourseDetailDTO::class, $result);
        $this->assertEquals($user->id, $result->createdBy);
        $this->assertCount(2, $result->modules);
        $this->assertEquals('First Module', $result->modules[0]->title);
        $this->assertEquals('Second Module', $result->modules[1]->title);
        $this->assertCount(2, $result->modules[0]->lessons);
        $this->assertEquals('Lesson A', $result->modules[0]->lessons[0]->title);
        $this->assertEquals('Lesson B', $result->modules[0]->lessons[1]->title);
        $this->assertCount(1, $result->modules[1]->lessons);
        $this->assertEquals('Lesson C', $result->modules[1]->lessons[0]->title);
    }

    public function test_get_course_with_structure_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->getCourseWithStructure(999);
    }

    public function test_delete_lesson_from_published_course_removes_lesson_and_dispatches_event(): void
    {
        Event::fake();

        $user = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Module', 'sort_order' => 1]);
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'To Delete',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $this->service->deleteLessonFromPublishedCourse($lesson->id);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        Event::assertDispatched(LessonRemovedFromCourse::class, function ($event) use ($course, $lesson) {
            return $event->courseId === $course->id && $event->lessonId === $lesson->id;
        });
    }

    public function test_delete_lesson_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->deleteLessonFromPublishedCourse(999);
    }
}
