<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Modules\Subscription\Events\SubscriptionActivated;
use Illuminate\Support\Facades\Log;

class SubscriptionActivatedListener
{
    public function handle(SubscriptionActivated $event): void
    {
        Log::info('Subscription activated for user', [
            'subscription_id' => $event->subscriptionId,
            'user_id' => $event->userId,
        ]);
    }
}
