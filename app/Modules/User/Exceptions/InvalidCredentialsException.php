<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

use App\Shared\Exceptions\BusinessException;

class InvalidCredentialsException extends BusinessException
{
    public function __construct(string $message = 'Invalid credentials.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
