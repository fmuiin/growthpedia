<?php

declare(strict_types=1);

namespace App\Modules\Payment\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaymentRequestDTO extends BaseDTO
{
    public function __construct(
        public string $amount,
        public string $currency,
        public string $token,
        public string $description,
    ) {}
}
