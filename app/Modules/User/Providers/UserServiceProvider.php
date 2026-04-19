<?php

declare(strict_types=1);

namespace App\Modules\User\Providers;

use App\Models\User;
use App\Modules\Subscription\Events\SubscriptionActivated;
use App\Modules\Subscription\Events\SubscriptionSuspended;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\Events\AccountLocked;
use App\Modules\User\Events\UserRegistered;
use App\Modules\User\Listeners\AccountLockedListener;
use App\Modules\User\Listeners\SubscriptionActivatedListener;
use App\Modules\User\Listeners\SubscriptionSuspendedListener;
use App\Modules\User\Listeners\UserRegisteredListener;
use App\Modules\User\Middleware\EnsureRole;
use App\Modules\User\Policies\UserPolicy;
use App\Modules\User\Services\UserService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Register the EnsureRole middleware alias
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('role', EnsureRole::class);

        // Register the UserPolicy for the User model
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(SubscriptionActivated::class, SubscriptionActivatedListener::class);
        Event::listen(SubscriptionSuspended::class, SubscriptionSuspendedListener::class);
        Event::listen(UserRegistered::class, UserRegisteredListener::class);
        Event::listen(AccountLocked::class, AccountLockedListener::class);
    }
}
