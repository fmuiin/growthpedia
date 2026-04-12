<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\Exceptions\VerificationExpiredException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function verify(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }

        try {
            $this->userService->verifyEmail((int) $request->route('id'));

            return redirect()->route('login')->with('success', 'Email verified successfully. You can now log in.');
        } catch (VerificationExpiredException) {
            return redirect()->route('login')->with('error', 'Verification link has expired. Please request a new one.');
        }
    }
}
