<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaginatedAdminUsersDTO extends BaseDTO
{
    /**
     * @param AdminUserDTO[] $users
     */
    public function __construct(
        public array $users,
        public int $total,
        public int $currentPage,
        public int $perPage,
        public int $lastPage,
    ) {}
}
