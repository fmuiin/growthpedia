<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Discussion\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentModelTest extends TestCase
{
    use RefreshDatabase;

    private function createLesson(): Lesson
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'description' => 'A test course',
            'category' => 'Testing',
            'status' => 'published',
        ]);
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        return Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Lesson content',
            'sort_order' => 1,
        ]);
    }

    public function test_comment_can_be_created_with_required_fields(): void
    {
        $user = User::factory()->create();
        $lesson = $this->createLesson();

        $comment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'This is a test comment.',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'This is a test comment.',
            'is_flagged' => false,
            'is_edited' => false,
        ]);
    }

    public function test_comment_belongs_to_lesson(): void
    {
        $user = User::factory()->create();
        $lesson = $this->createLesson();

        $comment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'Comment on lesson.',
        ]);

        $this->assertInstanceOf(Lesson::class, $comment->lesson);
        $this->assertEquals($lesson->id, $comment->lesson->id);
    }

    public function test_comment_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $lesson = $this->createLesson();

        $comment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'A comment.',
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals('John Doe', $comment->user->name);
    }

    public function test_comment_has_self_referencing_parent_and_replies(): void
    {
        $user = User::factory()->create();
        $lesson = $this->createLesson();

        $parentComment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'Parent comment.',
        ]);

        $reply = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'parent_comment_id' => $parentComment->id,
            'content' => 'Reply to parent.',
        ]);

        $this->assertInstanceOf(Comment::class, $reply->parent);
        $this->assertEquals($parentComment->id, $reply->parent->id);

        $parentComment->refresh();
        $this->assertCount(1, $parentComment->replies);
        $this->assertEquals($reply->id, $parentComment->replies->first()->id);
    }

    public function test_comment_parent_is_nullable(): void
    {
        $user = User::factory()->create();
        $lesson = $this->createLesson();

        $comment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'Top-level comment.',
        ]);

        $this->assertNull($comment->parent_comment_id);
        $this->assertNull($comment->parent);
    }

    public function test_comment_flagged_by_user_relationship(): void
    {
        $author = User::factory()->create();
        $flagger = User::factory()->create(['name' => 'Admin User']);
        $lesson = $this->createLesson();

        $comment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $author->id,
            'content' => 'Inappropriate content.',
            'is_flagged' => true,
            'flag_reason' => 'Spam',
            'flagged_by' => $flagger->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->flaggedByUser);
        $this->assertEquals('Admin User', $comment->flaggedByUser->name);
    }

    public function test_comment_casts_boolean_and_datetime_fields(): void
    {
        $user = User::factory()->create();
        $lesson = $this->createLesson();

        $comment = Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'Edited comment.',
            'is_flagged' => true,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        $comment->refresh();

        $this->assertIsBool($comment->is_flagged);
        $this->assertTrue($comment->is_flagged);
        $this->assertIsBool($comment->is_edited);
        $this->assertTrue($comment->is_edited);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $comment->edited_at);
    }
}
