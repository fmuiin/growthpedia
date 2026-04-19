<?php

declare(strict_types=1);

namespace App\Modules\Admin\Providers;

use App\Modules\Admin\Contracts\AnalyticsServiceInterface;
use App\Modules\Admin\Contracts\MembershipPlanServiceInterface;
use App\Modules\Admin\Middleware\EnsureAdmin;
use App\Modules\Admin\Services\AnalyticsService;
use App\Modules\Admin\Services\MembershipPlanService;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MembershipPlanServiceInterface::class, MembershipPlanService::class);
        $this->app->bind(AnalyticsServiceInterface::class, AnalyticsService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Register the EnsureAdmin middleware alias
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('admin', EnsureAdmin::class);
    }
}
