<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_belongs_to_instructor(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Test Course',
            'description' => 'A test course description',
            'category' => 'Programming',
        ]);

        $this->assertInstanceOf(User::class, $course->instructor);
        $this->assertEquals($user->id, $course->instructor->id);
    }

    public function test_course_has_many_modules(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        CourseModule::create(['course_id' => $course->id, 'title' => 'Module 1', 'sort_order' => 1]);
        CourseModule::create(['course_id' => $course->id, 'title' => 'Module 2', 'sort_order' => 2]);

        $this->assertCount(2, $course->modules);
    }

    public function test_course_modules_are_ordered_by_sort_order(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        CourseModule::create(['course_id' => $course->id, 'title' => 'Second', 'sort_order' => 2]);
        CourseModule::create(['course_id' => $course->id, 'title' => 'First', 'sort_order' => 1]);

        $modules = $course->modules;
        $this->assertEquals('First', $modules[0]->title);
        $this->assertEquals('Second', $modules[1]->title);
    }

    public function test_course_has_many_lessons_through_modules(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Module 1', 'sort_order' => 1]);
        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Some content',
            'sort_order' => 1,
        ]);

        $this->assertCount(1, $course->lessons);
    }

    public function test_course_default_status_is_draft(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        $course->refresh();
        $this->assertEquals('draft', $course->status);
    }

    public function test_course_published_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
            'published_at' => '2025-01-15 10:00:00',
        ]);

        $course->refresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $course->published_at);
    }
}
