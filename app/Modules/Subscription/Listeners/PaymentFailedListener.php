<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Listeners;

use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentFailedListener
{
    public function handle(PaymentFailed $event): void
    {
        $subscription = Subscription::where('user_id', $event->userId)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($subscription === null && $event->subscriptionId > 0) {
            $subscription = Subscription::find($event->subscriptionId);
        }

        if ($subscription === null) {
            Log::warning('PaymentFailedListener: No active subscription found', [
                'user_id' => $event->userId,
                'subscription_id' => $event->subscriptionId,
            ]);

            return;
        }

        $gracePeriodEndsAt = Carbon::now()->addDays(7);

        $subscription->update([
            'status' => 'grace_period',
            'grace_period_ends_at' => $gracePeriodEndsAt,
        ]);

        Log::info('Subscription moved to grace period due to payment failure', [
            'subscription_id' => $subscription->id,
            'user_id' => $event->userId,
            'grace_period_ends_at' => $gracePeriodEndsAt->toIso8601String(),
            'error_message' => $event->errorMessage,
        ]);

        $this->notifyLearner($subscription, $event);
    }

    private function notifyLearner(Subscription $subscription, PaymentFailed $event): void
    {
        $user = $subscription->user;

        if ($user === null) {
            Log::warning('PaymentFailedListener: User not found for notification', [
                'subscription_id' => $subscription->id,
                'user_id' => $event->userId,
            ]);

            return;
        }

        Mail::raw(
            "Dear {$user->name},\n\n"
            . "We were unable to process your subscription payment. "
            . "Your subscription has been placed in a 7-day grace period. "
            . "Please update your payment method to avoid losing access.\n\n"
            . "Error: {$event->errorMessage}\n\n"
            . "Thank you,\nGrowthPedia Team",
            function ($message) use ($user): void {
                $message->to($user->email)
                    ->subject('Payment Failed — Action Required');
            },
        );

        Log::info('Payment failure notification sent to learner', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
