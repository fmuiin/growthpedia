<?php

declare(strict_types=1);

namespace App\Modules\User\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class LoginDTO extends BaseDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
