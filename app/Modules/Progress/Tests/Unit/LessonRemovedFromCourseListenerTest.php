<?php

declare(strict_types=1);

namespace App\Modules\Progress\Tests\Unit;

use App\Modules\Course\Events\LessonRemovedFromCourse;
use App\Modules\Progress\Contracts\ProgressServiceInterface;
use App\Modules\Progress\Listeners\LessonRemovedFromCourseListener;
use Mockery;
use Tests\TestCase;

class LessonRemovedFromCourseListenerTest extends TestCase
{
    public function test_handle_calls_recalculate_for_course_with_correct_course_id(): void
    {
        $courseId = 15;
        $lessonId = 42;

        $progressService = Mockery::mock(ProgressServiceInterface::class);
        $progressService
            ->shouldReceive('recalculateForCourse')
            ->once()
            ->with($courseId);

        $listener = new LessonRemovedFromCourseListener($progressService);
        $event = new LessonRemovedFromCourse($courseId, $lessonId);

        $listener->handle($event);

        // Mockery assertions are verified automatically on tearDown
    }
}
