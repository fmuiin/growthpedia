<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Providers;

use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
