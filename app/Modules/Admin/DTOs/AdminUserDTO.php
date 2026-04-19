<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class AdminUserDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public ?string $subscriptionStatus,
        public DateTimeInterface $registrationDate,
        public bool $isSuspended,
    ) {}
}
