<?php

declare(strict_types=1);

namespace App\Modules\Subscription\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class PaymentTokenDTO extends BaseDTO
{
    public function __construct(
        public string $token,
        public string $gatewayType,
    ) {}
}
