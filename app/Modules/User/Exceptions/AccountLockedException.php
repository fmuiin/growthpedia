<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

use App\Shared\Exceptions\BusinessException;

class AccountLockedException extends BusinessException
{
    public function __construct(string $message = 'Account is locked. Please try again later.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 423, $previous);
    }
}
