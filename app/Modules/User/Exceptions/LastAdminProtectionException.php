<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

use App\Shared\Exceptions\BusinessException;

class LastAdminProtectionException extends BusinessException
{
    public function __construct(string $message = 'At least one admin account is required.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}
