<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Listeners;

use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Subscription\Events\SubscriptionActivated;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Support\Facades\Log;

class PaymentSucceededListener
{
    public function handle(PaymentSucceeded $event): void
    {
        $subscription = Subscription::where('user_id', $event->userId)
            ->whereIn('status', ['pending', 'grace_period'])
            ->latest()
            ->first();

        if ($subscription === null && $event->subscriptionId > 0) {
            $subscription = Subscription::find($event->subscriptionId);
        }

        if ($subscription === null) {
            Log::warning('PaymentSucceededListener: No subscription found', [
                'user_id' => $event->userId,
                'subscription_id' => $event->subscriptionId,
                'transaction_id' => $event->transactionId,
            ]);

            return;
        }

        $subscription->update([
            'status' => 'active',
            'grace_period_ends_at' => null,
        ]);

        Log::info('Subscription activated via payment webhook', [
            'subscription_id' => $subscription->id,
            'user_id' => $event->userId,
            'transaction_id' => $event->transactionId,
        ]);

        SubscriptionActivated::dispatch($subscription->id, $event->userId);
    }
}
