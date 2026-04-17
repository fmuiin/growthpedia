<?php

declare(strict_types=1);

namespace App\Modules\Progress\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LessonCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly int $lessonId,
        public readonly int $courseId,
        public readonly int $enrollmentId,
    ) {}
}
