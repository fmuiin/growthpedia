<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use DateTimeInterface;

class AccountLocked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly DateTimeInterface $lockedUntil,
    ) {}
}
