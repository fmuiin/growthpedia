<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Models\User;
use App\Modules\User\Events\AccountLocked;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountLockedListener
{
    public function handle(AccountLocked $event): void
    {
        $user = User::find($event->userId);

        if ($user === null) {
            Log::warning('AccountLockedListener: User not found', [
                'user_id' => $event->userId,
            ]);

            return;
        }

        $lockedUntil = $event->lockedUntil->format('Y-m-d H:i:s T');

        Mail::raw(
            "Dear {$user->name},\n\n"
            . "Your account has been locked due to multiple failed login attempts. "
            . "Your account will be unlocked at: {$lockedUntil}\n\n"
            . "If you did not attempt to log in, please contact support immediately.\n\n"
            . "Thank you,\nGrowthPedia Team",
            function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Account Locked — Security Notice');
            },
        );

        Log::info('Account lock notification sent to user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'locked_until' => $lockedUntil,
        ]);
    }
}
