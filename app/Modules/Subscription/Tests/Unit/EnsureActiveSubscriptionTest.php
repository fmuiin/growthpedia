<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Tests\Unit;

use App\Models\User;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Modules\Subscription\Middleware\EnsureActiveSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureActiveSubscriptionTest extends TestCase
{
    private SubscriptionServiceInterface $subscriptionService;
    private EnsureActiveSubscription $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionService = Mockery::mock(SubscriptionServiceInterface::class);
        $this->middleware = new EnsureActiveSubscription($this->subscriptionService);
    }

    #[Test]
    public function learner_with_active_subscription_passes(): void
    {
        $user = new User();
        $user->id = 1;
        $user->role = 'learner';

        $this->subscriptionService
            ->shouldReceive('hasActiveSubscription')
            ->with(1)
            ->once()
            ->andReturn(true);

        $request = Request::create('/lessons/1', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]
    public function learner_without_active_subscription_gets_403(): void
    {
        $user = new User();
        $user->id = 2;
        $user->role = 'learner';

        $this->subscriptionService
            ->shouldReceive('hasActiveSubscription')
            ->with(2)
            ->once()
            ->andReturn(false);

        $request = Request::create('/lessons/1', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);

        $this->middleware->handle($request, fn () => new Response('OK'));
    }

    #[Test]
    public function instructor_bypasses_subscription_check(): void
    {
        $user = new User();
        $user->id = 3;
        $user->role = 'instructor';

        $this->subscriptionService->shouldNotReceive('hasActiveSubscription');

        $request = Request::create('/lessons/1', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function admin_bypasses_subscription_check(): void
    {
        $user = new User();
        $user->id = 4;
        $user->role = 'admin';

        $this->subscriptionService->shouldNotReceive('hasActiveSubscription');

        $request = Request::create('/lessons/1', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function inertia_request_redirects_instead_of_403(): void
    {
        $user = new User();
        $user->id = 5;
        $user->role = 'learner';

        $this->subscriptionService
            ->shouldReceive('hasActiveSubscription')
            ->with(5)
            ->once()
            ->andReturn(false);

        $request = Request::create('/lessons/1', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-Inertia', 'true');

        $response = $this->middleware->handle($request, fn () => new Response('OK'));

        $this->assertTrue($response->isRedirect());
    }

    #[Test]
    public function unauthenticated_user_gets_403(): void
    {
        $request = Request::create('/lessons/1', 'GET');
        $request->setUserResolver(fn () => null);

        $this->expectException(HttpException::class);

        $this->middleware->handle($request, fn () => new Response('OK'));
    }
}
