<?php

declare(strict_types=1);

namespace App\Modules\Course\Providers;

use App\Modules\Course\Contracts\CourseServiceInterface;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Policies\CoursePolicy;
use App\Modules\Course\Services\CourseService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CourseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CourseServiceInterface::class, CourseService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        Gate::policy(Course::class, CoursePolicy::class);
    }
}
