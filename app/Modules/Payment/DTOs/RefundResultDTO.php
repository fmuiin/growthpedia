<?php

declare(strict_types=1);

namespace App\Modules\Payment\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class RefundResultDTO extends BaseDTO
{
    public function __construct(
        public bool $success,
        public ?string $refundId,
        public ?string $errorMessage,
    ) {}
}
