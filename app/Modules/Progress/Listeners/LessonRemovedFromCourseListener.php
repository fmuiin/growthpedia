<?php

declare(strict_types=1);

namespace App\Modules\Progress\Listeners;

use App\Modules\Course\Events\LessonRemovedFromCourse;
use App\Modules\Progress\Contracts\ProgressServiceInterface;

class LessonRemovedFromCourseListener
{
    public function __construct(
        private readonly ProgressServiceInterface $progressService,
    ) {}

    public function handle(LessonRemovedFromCourse $event): void
    {
        $this->progressService->recalculateForCourse($event->courseId);
    }
}
