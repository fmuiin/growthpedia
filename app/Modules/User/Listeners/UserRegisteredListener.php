<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Models\User;
use App\Modules\User\Events\UserRegistered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class UserRegisteredListener
{
    public function handle(UserRegistered $event): void
    {
        $user = User::find($event->userId);

        if ($user === null) {
            Log::warning('UserRegisteredListener: User not found', [
                'user_id' => $event->userId,
            ]);

            return;
        }

        $verificationUrl = URL::signedRoute('verification.verify', ['id' => $user->id]);

        Mail::raw(
            "Dear {$user->name},\n\n"
            . "Welcome to GrowthPedia! Please verify your email by clicking: {$verificationUrl}\n\n"
            . "This link will expire in 24 hours.\n\n"
            . "Thank you,\nGrowthPedia Team",
            function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Verify Your Email Address');
            },
        );

        Log::info('Verification email sent to new user', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
