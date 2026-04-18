<?php

declare(strict_types=1);

namespace App\Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentSucceeded
{
    use Dispatchable;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $userId,
        public readonly string $transactionId,
        public readonly string $amount,
    ) {}
}
