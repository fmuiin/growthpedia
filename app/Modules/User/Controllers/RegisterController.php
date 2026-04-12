<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\DTOs\RegisterDTO;
use App\Modules\User\Requests\RegisterRequest;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function showRegisterForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->userService->register(new RegisterDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        ));

        return redirect()->route('login')->with('success', 'Registration successful! Please check your email to verify your account.');
    }
}
