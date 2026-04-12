<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\DTOs\ResetPasswordDTO;
use App\Modules\User\Requests\ForgotPasswordRequest;
use App\Modules\User\Requests\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function showForgotForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $this->userService->requestPasswordReset($request->validated('email'));

        return back()->with('success', 'If an account exists with that email, a password reset link has been sent.');
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $this->userService->resetPassword(new ResetPasswordDTO(
                email: $request->validated('email'),
                token: $request->validated('token'),
                password: $request->validated('password'),
            ));

            return redirect()->route('login')->with('success', 'Password has been reset successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
