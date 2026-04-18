<?php

declare(strict_types=1);

namespace App\Modules\Discussion\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaginatedCommentsDTO extends BaseDTO
{
    /**
     * @param array<CommentDTO> $comments
     */
    public function __construct(
        public array $comments,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}
}
