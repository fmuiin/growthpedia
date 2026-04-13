<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $userId,
    ) {}
}
