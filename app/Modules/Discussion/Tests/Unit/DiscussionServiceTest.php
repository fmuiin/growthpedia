<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Discussion\DTOs\CommentDTO;
use App\Modules\Discussion\DTOs\PaginatedCommentsDTO;
use App\Modules\Discussion\Events\CommentFlagged;
use App\Modules\Discussion\Exceptions\CommentingNotAllowedException;
use App\Modules\Discussion\Exceptions\UnauthorizedCommentEditException;
use App\Modules\Discussion\Models\Comment;
use App\Modules\Discussion\Services\DiscussionService;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DiscussionServiceTest extends TestCase
{
    use RefreshDatabase;

    private DiscussionService $service;
    private SubscriptionServiceInterface $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionService = $this->createMock(SubscriptionServiceInterface::class);
        $this->service = new DiscussionService($this->subscriptionService);
    }

    private function createLesson(): Lesson
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
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

    // --- createComment() ---

    public function test_create_comment_with_active_subscription(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $result = $this->service->createComment($user->id, $lesson->id, 'Great lesson!');

        $this->assertInstanceOf(CommentDTO::class, $result);
        $this->assertEquals($lesson->id, $result->lessonId);
        $this->assertEquals($user->id, $result->userId);
        $this->assertEquals('Great lesson!', $result->content);
        $this->assertEquals('John Doe', $result->authorName);
        $this->assertNull($result->parentCommentId);
        $this->assertFalse($result->isFlagged);
        $this->assertFalse($result->isEdited);

        $this->assertDatabaseHas('comments', [
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'content' => 'Great lesson!',
        ]);
    }

    public function test_create_comment_as_instructor_without_subscription(): void
    {
        $instructor = User::factory()->create(['name' => 'Prof Smith', 'role' => 'instructor']);
        $lesson = $this->createLesson();

        // Instructor should not need a subscription check
        $this->subscriptionService->method('hasActiveSubscription')->willReturn(false);

        $result = $this->service->createComment($instructor->id, $lesson->id, 'Instructor comment');

        $this->assertInstanceOf(CommentDTO::class, $result);
        $this->assertEquals('Instructor comment', $result->content);
        $this->assertEquals('Prof Smith', $result->authorName);
    }

    public function test_create_comment_as_admin_without_subscription(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'role' => 'admin']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(false);

        $result = $this->service->createComment($admin->id, $lesson->id, 'Admin comment');

        $this->assertInstanceOf(CommentDTO::class, $result);
        $this->assertEquals('Admin comment', $result->content);
    }

    public function test_create_comment_throws_when_no_active_subscription(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(false);

        $this->expectException(CommentingNotAllowedException::class);
        $this->expectExceptionMessage('Active subscription required to post comments.');

        $this->service->createComment($user->id, $lesson->id, 'Should fail');
    }

    public function test_create_comment_throws_when_user_not_found(): void
    {
        $lesson = $this->createLesson();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('User not found.');

        $this->service->createComment(99999, $lesson->id, 'No user');
    }

    // --- replyToComment() ---

    public function test_reply_to_comment_creates_nested_reply(): void
    {
        $user = User::factory()->create(['name' => 'Alice', 'role' => 'learner']);
        $replier = User::factory()->create(['name' => 'Bob', 'role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $parent = $this->service->createComment($user->id, $lesson->id, 'Original comment');
        $reply = $this->service->replyToComment($replier->id, $parent->id, 'This is a reply');

        $this->assertInstanceOf(CommentDTO::class, $reply);
        $this->assertEquals($parent->id, $reply->parentCommentId);
        $this->assertEquals($lesson->id, $reply->lessonId);
        $this->assertEquals('This is a reply', $reply->content);
        $this->assertEquals('Bob', $reply->authorName);
    }

    public function test_reply_to_comment_throws_when_parent_not_found(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Parent comment not found.');

        $this->service->replyToComment($user->id, 99999, 'Reply to nothing');
    }

    public function test_reply_to_comment_throws_when_no_subscription(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $poster = User::factory()->create(['role' => 'instructor']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(false);

        // Create parent comment as instructor (bypasses subscription check)
        $parent = Comment::create([
            'user_id' => $poster->id,
            'lesson_id' => $lesson->id,
            'content' => 'Parent comment',
        ]);

        $this->expectException(CommentingNotAllowedException::class);

        $this->service->replyToComment($user->id, $parent->id, 'Should fail');
    }

    // --- editComment() ---

    public function test_edit_comment_updates_content_and_sets_edited_flag(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-07-01 12:00:00'));

        $user = User::factory()->create(['name' => 'Editor', 'role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $comment = $this->service->createComment($user->id, $lesson->id, 'Original content');

        Carbon::setTestNow(Carbon::parse('2024-07-01 13:00:00'));

        $edited = $this->service->editComment($user->id, $comment->id, 'Updated content');

        $this->assertInstanceOf(CommentDTO::class, $edited);
        $this->assertEquals('Updated content', $edited->content);
        $this->assertTrue($edited->isEdited);
        $this->assertEquals('2024-07-01 13:00:00', $edited->editedAt->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
            'is_edited' => true,
        ]);
    }

    public function test_edit_comment_throws_when_not_owner(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $other = User::factory()->create(['role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $comment = $this->service->createComment($owner->id, $lesson->id, 'My comment');

        $this->expectException(UnauthorizedCommentEditException::class);
        $this->expectExceptionMessage('You can only edit your own comments.');

        $this->service->editComment($other->id, $comment->id, 'Trying to edit');
    }

    public function test_edit_comment_throws_when_comment_not_found(): void
    {
        $user = User::factory()->create();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->service->editComment($user->id, 99999, 'No comment');
    }

    // --- flagComment() ---

    public function test_flag_comment_sets_flag_and_dispatches_event(): void
    {
        Event::fake([CommentFlagged::class]);

        $user = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $comment = $this->service->createComment($user->id, $lesson->id, 'Inappropriate content');

        $this->service->flagComment($admin->id, $comment->id, 'Spam');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_flagged' => true,
            'flag_reason' => 'Spam',
            'flagged_by' => $admin->id,
        ]);

        Event::assertDispatched(CommentFlagged::class, function (CommentFlagged $event) use ($comment, $admin) {
            return $event->commentId === $comment->id
                && $event->flaggedBy === $admin->id
                && $event->reason === 'Spam';
        });
    }

    public function test_flag_comment_throws_when_comment_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->service->flagComment($admin->id, 99999, 'Reason');
    }

    // --- getThreadForLesson() ---

    public function test_get_thread_returns_comments_in_chronological_order(): void
    {
        $user = User::factory()->create(['name' => 'Commenter', 'role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        Carbon::setTestNow(Carbon::parse('2024-01-01 10:00:00'));
        $this->service->createComment($user->id, $lesson->id, 'First comment');

        Carbon::setTestNow(Carbon::parse('2024-01-01 11:00:00'));
        $this->service->createComment($user->id, $lesson->id, 'Second comment');

        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));
        $this->service->createComment($user->id, $lesson->id, 'Third comment');

        $result = $this->service->getThreadForLesson($lesson->id, 1);

        $this->assertInstanceOf(PaginatedCommentsDTO::class, $result);
        $this->assertCount(3, $result->comments);
        $this->assertEquals('First comment', $result->comments[0]->content);
        $this->assertEquals('Second comment', $result->comments[1]->content);
        $this->assertEquals('Third comment', $result->comments[2]->content);
    }

    public function test_get_thread_excludes_flagged_comments(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $comment1 = $this->service->createComment($user->id, $lesson->id, 'Visible comment');
        $comment2 = $this->service->createComment($user->id, $lesson->id, 'Flagged comment');

        $this->service->flagComment($admin->id, $comment2->id, 'Inappropriate');

        $result = $this->service->getThreadForLesson($lesson->id, 1);

        $this->assertCount(1, $result->comments);
        $this->assertEquals('Visible comment', $result->comments[0]->content);
    }

    public function test_get_thread_excludes_flagged_replies(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $parent = $this->service->createComment($user->id, $lesson->id, 'Parent comment');
        $reply1 = $this->service->replyToComment($user->id, $parent->id, 'Good reply');
        $reply2 = $this->service->replyToComment($user->id, $parent->id, 'Bad reply');

        $this->service->flagComment($admin->id, $reply2->id, 'Spam');

        $result = $this->service->getThreadForLesson($lesson->id, 1);

        $this->assertCount(1, $result->comments);
        $this->assertCount(1, $result->comments[0]->replies);
        $this->assertEquals('Good reply', $result->comments[0]->replies[0]->content);
    }

    public function test_get_thread_includes_replies_with_author_names(): void
    {
        $user = User::factory()->create(['name' => 'Alice', 'role' => 'learner']);
        $replier = User::factory()->create(['name' => 'Bob', 'role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        $parent = $this->service->createComment($user->id, $lesson->id, 'Parent');
        $this->service->replyToComment($replier->id, $parent->id, 'Reply from Bob');

        $result = $this->service->getThreadForLesson($lesson->id, 1);

        $this->assertCount(1, $result->comments);
        $this->assertEquals('Alice', $result->comments[0]->authorName);
        $this->assertCount(1, $result->comments[0]->replies);
        $this->assertEquals('Bob', $result->comments[0]->replies[0]->authorName);
    }

    public function test_get_thread_returns_pagination_metadata(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $lesson = $this->createLesson();

        $this->subscriptionService->method('hasActiveSubscription')->willReturn(true);

        // Create 16 comments to trigger pagination (15 per page)
        for ($i = 1; $i <= 16; $i++) {
            $this->service->createComment($user->id, $lesson->id, "Comment {$i}");
        }

        $page1 = $this->service->getThreadForLesson($lesson->id, 1);
        $page2 = $this->service->getThreadForLesson($lesson->id, 2);

        $this->assertEquals(1, $page1->currentPage);
        $this->assertEquals(2, $page1->lastPage);
        $this->assertEquals(15, $page1->perPage);
        $this->assertEquals(16, $page1->total);
        $this->assertCount(15, $page1->comments);

        $this->assertEquals(2, $page2->currentPage);
        $this->assertCount(1, $page2->comments);
    }

    public function test_get_thread_returns_empty_for_lesson_with_no_comments(): void
    {
        $lesson = $this->createLesson();

        $result = $this->service->getThreadForLesson($lesson->id, 1);

        $this->assertInstanceOf(PaginatedCommentsDTO::class, $result);
        $this->assertCount(0, $result->comments);
        $this->assertEquals(0, $result->total);
    }
}
