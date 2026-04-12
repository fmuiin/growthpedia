<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly int $statusCode = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
