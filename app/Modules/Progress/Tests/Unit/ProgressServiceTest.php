<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tests\Unit;

use App\Models\User;
use App\Modules\Course\DTOs\LessonDTO;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Progress\DTOs\CourseProgressDTO;
use App\Modules\Progress\DTOs\ProgressDTO;
use App\Modules\Progress\Events\CourseCompleted;
use App\Modules\Progress\Events\LessonCompleted;
use App\Modules\Progress\Models\Enrollment;
use App\Modules\Progress\Models\LessonProgress;
use App\Modules\Progress\Services\ProgressService;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProgressService();
    }

    private function createCourseWithLessons(int $lessonCount = 3): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Testing',
            'status' => 'published',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $lessons = [];
        for ($i = 1; $i <= $lessonCount; $i++) {
            $lessons[] = Lesson::create([
                'course_module_id' => $module->id,
                'title' => "Lesson {$i}",
                'content_type' => 'text',
                'content_body' => "Content {$i}",
                'sort_order' => $i,
            ]);
        }

        return [$course, $module, $lessons];
    }

    // --- markLessonComplete() ---

    public function test_mark_lesson_complete_creates_enrollment_and_progress(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-01 12:00:00'));

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(3);

        $result = $this->service->markLessonComplete($user->id, $lessons[0]->id);

        $this->assertInstanceOf(ProgressDTO::class, $result);
        $this->assertEquals($lessons[0]->id, $result->lessonId);
        $this->assertNotNull($result->completedAt);

        // Enrollment should be created
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        $this->assertNotNull($enrollment);

        // Completion percentage should be 1/3 * 100 ≈ 33.33
        $this->assertEqualsWithDelta(33.33, (float) $enrollment->completion_percentage, 0.01);

        Event::assertDispatched(LessonCompleted::class, function ($e) use ($user, $lessons, $course) {
            return $e->userId === $user->id
                && $e->lessonId === $lessons[0]->id
                && $e->courseId === $course->id;
        });
    }

    public function test_mark_lesson_complete_updates_percentage_correctly(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(4);

        // Complete first two lessons
        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[1]->id);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        // 2/4 * 100 = 50
        $this->assertEqualsWithDelta(50.0, (float) $enrollment->completion_percentage, 0.01);
    }

    public function test_mark_lesson_complete_dispatches_course_completed_at_100_percent(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(2);

        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[1]->id);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertEqualsWithDelta(100.0, (float) $enrollment->completion_percentage, 0.01);
        $this->assertNotNull($enrollment->completed_at);

        Event::assertDispatched(CourseCompleted::class, function ($e) use ($user, $course) {
            return $e->userId === $user->id && $e->courseId === $course->id;
        });
    }

    public function test_mark_lesson_complete_is_idempotent(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(3);

        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[0]->id);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        // Still 1/3 because duplicate is prevented by firstOrCreate
        $this->assertEqualsWithDelta(33.33, (float) $enrollment->completion_percentage, 0.01);
        $this->assertEquals(1, $enrollment->lessonProgress()->count());
    }

    public function test_mark_lesson_complete_throws_when_lesson_not_found(): void
    {
        $user = User::factory()->create();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Lesson not found.');
        $this->service->markLessonComplete($user->id, 999);
    }

    // --- getCourseProgress() ---

    public function test_get_course_progress_returns_correct_counts(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(5);

        // Complete 2 of 5 lessons
        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[1]->id);

        $progress = $this->service->getCourseProgress($user->id, $course->id);

        $this->assertInstanceOf(CourseProgressDTO::class, $progress);
        $this->assertEquals($course->id, $progress->courseId);
        $this->assertEqualsWithDelta(40.0, $progress->completionPercentage, 0.01);
        $this->assertEquals(2, $progress->completedCount);
        $this->assertEquals(3, $progress->remainingCount);
        $this->assertNull($progress->completedAt);
    }

    public function test_get_course_progress_throws_when_enrollment_not_found(): void
    {
        $user = User::factory()->create();
        [$course] = $this->createCourseWithLessons(1);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Enrollment not found.');
        $this->service->getCourseProgress($user->id, $course->id);
    }

    // --- getNextLesson() ---

    public function test_get_next_lesson_returns_first_incomplete_lesson(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $module1 = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'sort_order' => 1]);
        $module2 = CourseModule::create(['course_id' => $course->id, 'title' => 'M2', 'sort_order' => 2]);

        $lesson1 = Lesson::create(['course_module_id' => $module1->id, 'title' => 'L1', 'content_type' => 'text', 'sort_order' => 1]);
        $lesson2 = Lesson::create(['course_module_id' => $module1->id, 'title' => 'L2', 'content_type' => 'text', 'sort_order' => 2]);
        $lesson3 = Lesson::create(['course_module_id' => $module2->id, 'title' => 'L3', 'content_type' => 'text', 'sort_order' => 1]);

        // Complete first lesson
        $this->service->markLessonComplete($user->id, $lesson1->id);

        $next = $this->service->getNextLesson($user->id, $course->id);

        $this->assertInstanceOf(LessonDTO::class, $next);
        $this->assertEquals($lesson2->id, $next->id);
        $this->assertEquals('L2', $next->title);
    }

    public function test_get_next_lesson_returns_null_when_all_complete(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(2);

        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[1]->id);

        $next = $this->service->getNextLesson($user->id, $course->id);

        $this->assertNull($next);
    }

    public function test_get_next_lesson_throws_when_enrollment_not_found(): void
    {
        $user = User::factory()->create();
        [$course] = $this->createCourseWithLessons(1);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Enrollment not found.');
        $this->service->getNextLesson($user->id, $course->id);
    }

    // --- recalculateForCourse() ---

    public function test_recalculate_for_course_updates_percentages_after_lesson_removal(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(4);

        // Complete lessons 1 and 2
        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[1]->id);

        // Verify initial state: 2/4 = 50%
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        $this->assertEqualsWithDelta(50.0, (float) $enrollment->completion_percentage, 0.01);

        // Remove lesson 3 (uncompleted) — now 3 total, 2 completed
        $lessons[2]->delete();

        $this->service->recalculateForCourse($course->id);

        $enrollment->refresh();
        // 2/3 * 100 ≈ 66.67
        $this->assertEqualsWithDelta(66.67, (float) $enrollment->completion_percentage, 0.01);
    }

    public function test_recalculate_for_course_handles_completed_lesson_removal(): void
    {
        Event::fake();

        $user = User::factory()->create();
        [$course, $module, $lessons] = $this->createCourseWithLessons(3);

        // Complete all 3 lessons
        $this->service->markLessonComplete($user->id, $lessons[0]->id);
        $this->service->markLessonComplete($user->id, $lessons[1]->id);
        $this->service->markLessonComplete($user->id, $lessons[2]->id);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        $this->assertNotNull($enrollment->completed_at);

        // Remove a completed lesson — delete its progress first to simulate cascade, then remove lesson
        LessonProgress::where('lesson_id', $lessons[2]->id)->delete();
        $lessons[2]->delete();

        $this->service->recalculateForCourse($course->id);

        $enrollment->refresh();
        // 2 completed that still exist / 2 total = 100%
        $this->assertEqualsWithDelta(100.0, (float) $enrollment->completion_percentage, 0.01);
    }

    public function test_recalculate_for_course_clears_completed_at_when_no_longer_100(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'M1', 'sort_order' => 1]);
        $lesson1 = Lesson::create(['course_module_id' => $module->id, 'title' => 'L1', 'content_type' => 'text', 'sort_order' => 1]);

        // Complete the only lesson → 100%
        $this->service->markLessonComplete($user->id, $lesson1->id);

        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($enrollment->completed_at);

        // Add a new lesson to the course
        Lesson::create(['course_module_id' => $module->id, 'title' => 'L2', 'content_type' => 'text', 'sort_order' => 2]);

        $this->service->recalculateForCourse($course->id);

        $enrollment->refresh();
        // 1/2 = 50%, completed_at should be cleared
        $this->assertEqualsWithDelta(50.0, (float) $enrollment->completion_percentage, 0.01);
        $this->assertNull($enrollment->completed_at);
    }

    public function test_recalculate_for_course_does_nothing_for_nonexistent_course(): void
    {
        // Should not throw
        $this->service->recalculateForCourse(999);
        $this->assertTrue(true);
    }
}
