<?php

declare(strict_types=1);

namespace App\Modules\Payment\Contracts;

use App\Modules\Payment\DTOs\PaymentRequestDTO;
use App\Modules\Payment\DTOs\PaymentResultDTO;
use App\Modules\Payment\DTOs\RefundResultDTO;

interface PaymentGatewayInterface
{
    public function charge(PaymentRequestDTO $request): PaymentResultDTO;

    public function refund(string $transactionId, int $amount): RefundResultDTO;

    public function verifyWebhookSignature(string $payload, string $signature): bool;

    public function retryCharge(PaymentRequestDTO $request, int $maxRetries = 3): PaymentResultDTO;
}
