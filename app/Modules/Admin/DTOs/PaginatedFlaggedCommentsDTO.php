<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaginatedFlaggedCommentsDTO extends BaseDTO
{
    /**
     * @param FlaggedCommentDTO[] $comments
     */
    public function __construct(
        public array $comments,
        public int $total,
        public int $currentPage,
        public int $perPage,
        public int $lastPage,
    ) {}
}
