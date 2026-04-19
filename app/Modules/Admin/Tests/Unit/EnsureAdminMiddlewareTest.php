<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests\Unit;

use App\Models\User;
use App\Modules\Admin\Middleware\EnsureAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureAdminMiddlewareTest extends TestCase
{
    private EnsureAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureAdmin();
    }

    public function test_admin_user_passes_through(): void
    {
        $user = new User(['role' => 'admin']);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/admin/users');
        $called = false;

        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return response('OK');
        });

        $this->assertTrue($called);
    }

    public function test_learner_user_is_rejected(): void
    {
        $user = new User(['role' => 'learner']);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/admin/users');

        $this->expectException(HttpException::class);

        $this->middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_instructor_user_is_rejected(): void
    {
        $user = new User(['role' => 'instructor']);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/admin/users');

        $this->expectException(HttpException::class);

        $this->middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        Auth::shouldReceive('user')->andReturn(null);

        $request = Request::create('/admin/users');

        $this->expectException(HttpException::class);

        $this->middleware->handle($request, function () {
            return response('OK');
        });
    }
}
