<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseModuleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_belongs_to_course(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $this->assertInstanceOf(Course::class, $module->course);
        $this->assertEquals($course->id, $module->course->id);
    }

    public function test_module_has_many_lessons(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);
        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 2',
            'content_type' => 'video',
            'video_url' => 'https://example.com/video.mp4',
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $module->lessons);
    }

    public function test_module_lessons_are_ordered_by_sort_order(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Second Lesson',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 2,
        ]);
        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'First Lesson',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $lessons = $module->lessons;
        $this->assertEquals('First Lesson', $lessons[0]->title);
        $this->assertEquals('Second Lesson', $lessons[1]->title);
    }

    public function test_deleting_course_cascades_to_modules(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $course->delete();
        $this->assertDatabaseCount('course_modules', 0);
    }
}
