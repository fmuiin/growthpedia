<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Exceptions;

use App\Shared\Exceptions\BusinessException;

class PaymentFailedException extends BusinessException
{
    public function __construct(string $message = 'Payment processing failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}
