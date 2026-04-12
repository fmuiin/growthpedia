<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

use App\Shared\Exceptions\BusinessException;

class VerificationExpiredException extends BusinessException
{
    public function __construct(string $message = 'Verification link has expired. Please request a new one.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 410, $previous);
    }
}
