<?php

declare(strict_types=1);

namespace App\Modules\User\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class UserDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public ?DateTimeInterface $email_verified_at,
        public bool $is_suspended,
        public DateTimeInterface $created_at,
    ) {}
}
