<?php

declare(strict_types=1);

namespace App\Modules\Progress\Providers;

use App\Modules\Course\Events\LessonRemovedFromCourse;
use App\Modules\Progress\Contracts\ProgressServiceInterface;
use App\Modules\Progress\Events\LessonCompleted;
use App\Modules\Progress\Listeners\LessonCompletedListener;
use App\Modules\Progress\Listeners\LessonRemovedFromCourseListener;
use App\Modules\Progress\Services\ProgressService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ProgressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProgressServiceInterface::class, ProgressService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        Event::listen(LessonCompleted::class, LessonCompletedListener::class);
        Event::listen(LessonRemovedFromCourse::class, LessonRemovedFromCourseListener::class);
    }
}
