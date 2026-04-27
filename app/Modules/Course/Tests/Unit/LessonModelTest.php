<?php

declare(strict_types=1);

namespace App\Modules\Course\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonModelTest extends TestCase
{
    use RefreshDatabase;

    private function createModuleWithCourse(): CourseModule
    {
        $user = User::factory()->create();
        $course = Course::create([
            'created_by' => $user->id,
            'title' => 'Test Course',
            'description' => 'Description',
            'category' => 'Design',
        ]);

        return CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);
    }

    public function test_lesson_belongs_to_module(): void
    {
        $module = $this->createModuleWithCourse();
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Some content',
            'sort_order' => 1,
        ]);

        $this->assertInstanceOf(CourseModule::class, $lesson->module);
        $this->assertEquals($module->id, $lesson->module->id);
    }

    public function test_lesson_supports_text_content_type(): void
    {
        $module = $this->createModuleWithCourse();
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Text Lesson',
            'content_type' => 'text',
            'content_body' => 'This is text content',
            'sort_order' => 1,
        ]);

        $this->assertEquals('text', $lesson->content_type);
        $this->assertEquals('This is text content', $lesson->content_body);
        $this->assertNull($lesson->video_url);
    }

    public function test_lesson_supports_video_content_type(): void
    {
        $module = $this->createModuleWithCourse();
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Video Lesson',
            'content_type' => 'video',
            'video_url' => 'https://example.com/video.mp4',
            'sort_order' => 1,
        ]);

        $this->assertEquals('video', $lesson->content_type);
        $this->assertEquals('https://example.com/video.mp4', $lesson->video_url);
    }

    public function test_lesson_supports_mixed_content_type(): void
    {
        $module = $this->createModuleWithCourse();
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Mixed Lesson',
            'content_type' => 'mixed',
            'content_body' => 'Some text',
            'video_url' => 'https://example.com/video.mp4',
            'sort_order' => 1,
        ]);

        $this->assertEquals('mixed', $lesson->content_type);
        $this->assertNotNull($lesson->content_body);
        $this->assertNotNull($lesson->video_url);
    }

    public function test_deleting_module_cascades_to_lessons(): void
    {
        $module = $this->createModuleWithCourse();
        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $module->delete();
        $this->assertDatabaseCount('lessons', 0);
    }
}
