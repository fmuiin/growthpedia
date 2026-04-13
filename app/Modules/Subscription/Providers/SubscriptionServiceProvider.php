<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Providers;

use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
