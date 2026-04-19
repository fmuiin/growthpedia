<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Modules\Subscription\Events\SubscriptionSuspended;
use Illuminate\Support\Facades\Log;

class SubscriptionSuspendedListener
{
    public function handle(SubscriptionSuspended $event): void
    {
        Log::info('Subscription suspended for user', [
            'subscription_id' => $event->subscriptionId,
            'user_id' => $event->userId,
        ]);
    }
}
