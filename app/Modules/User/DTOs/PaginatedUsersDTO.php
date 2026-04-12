<?php

declare(strict_types=1);

namespace App\Modules\User\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaginatedUsersDTO extends BaseDTO
{
    /**
     * @param UserDTO[] $users
     */
    public function __construct(
        public array $users,
        public int $total,
        public int $currentPage,
        public int $perPage,
    ) {}
}
