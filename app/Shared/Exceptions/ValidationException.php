<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

/**
 * Domain-level validation failure (not Laravel form validation).
 */
class ValidationException extends BusinessException
{
    public function __construct(string $message = 'Validation failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}
