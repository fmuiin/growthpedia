<?php

declare(strict_types=1);

namespace App\Modules\Course\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LessonRemovedFromCourse
{
    use Dispatchable;

    public function __construct(
        public readonly int $courseId,
        public readonly int $lessonId,
    ) {}
}
