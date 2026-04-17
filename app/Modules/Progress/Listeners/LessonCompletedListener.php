<?php

declare(strict_types=1);

namespace App\Modules\Progress\Listeners;

use App\Modules\Progress\Events\LessonCompleted;

class LessonCompletedListener
{
    public function __construct()
    {
    }

    /**
     * Handle the LessonCompleted event.
     *
     * The ProgressService already handles progress recalculation and
     * dispatches CourseCompleted when 100% is reached inside markLessonComplete().
     * This listener serves as a hook point for additional side effects
     * (e.g., logging, notifications, analytics) in the future.
     */
    public function handle(LessonCompleted $event): void
    {
        // Hook point for future side effects such as:
        // - Sending progress milestone notifications
        // - Logging lesson completion analytics
        // - Triggering gamification rewards
    }
}
