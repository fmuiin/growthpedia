<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Middleware;

use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    /**
     * Handle an incoming request — require an active subscription.
     *
     * Instructors and admins bypass the subscription check since they
     * need access to manage content regardless of subscription status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Authentication required.');
        }

        // Instructors and admins always have access
        if (in_array($user->role, ['instructor', 'admin'], true)) {
            return $next($request);
        }

        if ($this->subscriptionService->hasActiveSubscription($user->id)) {
            return $next($request);
        }

        // For Inertia requests, redirect with a flash error instead of a raw 403
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('subscription.plans')
                ->with('error', 'Active subscription required. Please subscribe or renew.');
        }

        abort(403, 'Active subscription required. Please subscribe or renew.');
    }
}
