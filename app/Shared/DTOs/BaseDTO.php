<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

abstract readonly class BaseDTO
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
