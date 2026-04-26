<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\User\Contracts\UserServiceInterface;
use App\Modules\User\Exceptions\VerificationExpiredException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

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

    public function resend(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return back()->with('success', 'Your email is already verified.');
        }

        $verificationUrl = URL::signedRoute('verification.verify', ['id' => $user->id]);

        Mail::raw(
            "Dear {$user->name},\n\n"
            . "Please verify your email by clicking: {$verificationUrl}\n\n"
            . "This link will expire in 24 hours.\n\n"
            . "Thank you,\nGrowthPedia Team",
            function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Verify Your Email Address');
            },
        );

        return back()->with('success', 'Verification email sent! Please check your inbox.');
    }
}
