<?php

declare(strict_types=1);

namespace App\Modules\Payment\Exceptions;

use App\Shared\Exceptions\BusinessException;

class PaymentGatewayException extends BusinessException
{
    public function __construct(string $message = 'Payment gateway error.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 502, $previous);
    }
}
