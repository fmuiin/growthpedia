<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Exceptions;

use App\Shared\Exceptions\BusinessException;

class PlanNotActiveException extends BusinessException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The selected membership plan is not active.', 422, $previous);
    }
}
