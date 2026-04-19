<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Providers;

use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Modules\Subscription\Listeners\PaymentFailedListener;
use App\Modules\Subscription\Listeners\PaymentSucceededListener;
use App\Modules\Subscription\Middleware\EnsureActiveSubscription;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Support\Facades\Event;
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

        // Register the EnsureActiveSubscription middleware alias
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('subscription', EnsureActiveSubscription::class);

        Event::listen(PaymentSucceeded::class, PaymentSucceededListener::class);
        Event::listen(PaymentFailed::class, PaymentFailedListener::class);
    }
}
