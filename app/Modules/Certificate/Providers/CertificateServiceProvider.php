<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Providers;

use App\Modules\Certificate\Contracts\CertificateServiceInterface;
use App\Modules\Certificate\Listeners\CourseCompletedListener;
use App\Modules\Certificate\Services\CertificateService;
use App\Modules\Progress\Events\CourseCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CertificateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CertificateServiceInterface::class, CertificateService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        Event::listen(CourseCompleted::class, CourseCompletedListener::class);
    }
}
