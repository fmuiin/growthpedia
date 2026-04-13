<?php

declare(strict_types=1);

namespace App\Modules\Admin\Exceptions;

use App\Shared\Exceptions\BusinessException;

class PlanHasActiveSubscriptionsException extends BusinessException
{
    public function __construct(public readonly int $activeCount)
    {
        parent::__construct(
            message: "Cannot delete plan: {$activeCount} active subscription(s) exist.",
            statusCode: 422,
        );
    }
}
