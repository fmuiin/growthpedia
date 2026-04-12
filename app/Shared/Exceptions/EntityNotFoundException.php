<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class EntityNotFoundException extends BusinessException
{
    public function __construct(string $message = 'Entity not found.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
