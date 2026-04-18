<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CommentFlagged
{
    use Dispatchable;

    public function __construct(
        public readonly int $commentId,
        public readonly int $flaggedBy,
        public readonly string $reason,
    ) {}
}
