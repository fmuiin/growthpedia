<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\DTOs\LoginDTO;
use App\Modules\User\Exceptions\AccountLockedException;
use App\Modules\User\Exceptions\InvalidCredentialsException;
use App\Modules\User\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        try {
            $userDTO = $this->userService->attemptLogin(new LoginDTO(
                email: $request->validated('email'),
                password: $request->validated('password'),
            ));

            $user = \App\Models\User::findOrFail($userDTO->id);
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        } catch (AccountLockedException $e) {
            return back()->with('error', $e->getMessage());
        } catch (InvalidCredentialsException) {
            return back()->with('error', 'Invalid credentials.');
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
