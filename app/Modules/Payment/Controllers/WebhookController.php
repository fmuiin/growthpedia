<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentRefunded;
use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Payment\Exceptions\InvalidWebhookSignatureException;
use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature')
            ?? $request->header('X-Webhook-Signature')
            ?? '';

        if (!$this->paymentGateway->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            throw new InvalidWebhookSignatureException();
        }

        $data = json_decode($payload, true);

        if (!is_array($data)) {
            Log::warning('Webhook payload is not valid JSON');

            return new JsonResponse(['message' => 'Invalid payload'], 400);
        }

        $eventType = $data['type'] ?? null;

        Log::info('Webhook received', ['type' => $eventType]);

        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($data),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($data),
            'charge.refunded' => $this->handleChargeRefunded($data),
            default => Log::info('Unhandled webhook event type', ['type' => $eventType]),
        };

        return new JsonResponse(['message' => 'Webhook processed'], 200);
    }

    private function handlePaymentSucceeded(array $data): void
    {
        $paymentData = $data['data']['object'] ?? [];

        $subscriptionId = (int) ($paymentData['metadata']['subscription_id'] ?? 0);
        $userId = (int) ($paymentData['metadata']['user_id'] ?? 0);
        $transactionId = $paymentData['id'] ?? '';
        $amount = (string) (($paymentData['amount'] ?? 0) / 100);
        $currency = strtoupper($paymentData['currency'] ?? 'IDR');

        PaymentTransaction::create([
            'subscription_id' => $subscriptionId ?: null,
            'gateway_transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'success',
            'type' => 'charge',
            'metadata' => $paymentData['metadata'] ?? null,
        ]);

        Log::info('Payment succeeded', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'subscription_id' => $subscriptionId,
            'user_id' => $userId,
        ]);

        PaymentSucceeded::dispatch($subscriptionId, $userId, $transactionId, $amount);
    }

    private function handlePaymentFailed(array $data): void
    {
        $paymentData = $data['data']['object'] ?? [];

        $subscriptionId = (int) ($paymentData['metadata']['subscription_id'] ?? 0);
        $userId = (int) ($paymentData['metadata']['user_id'] ?? 0);
        $transactionId = $paymentData['id'] ?? 'failed_' . uniqid();
        $errorMessage = $paymentData['last_payment_error']['message']
            ?? $data['data']['object']['failure_message']
            ?? 'Payment failed';
        $amount = (string) (($paymentData['amount'] ?? 0) / 100);
        $currency = strtoupper($paymentData['currency'] ?? 'IDR');

        PaymentTransaction::create([
            'subscription_id' => $subscriptionId ?: null,
            'gateway_transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'failed',
            'type' => 'charge',
            'metadata' => ['error' => $errorMessage],
        ]);

        Log::info('Payment failed', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'subscription_id' => $subscriptionId,
            'user_id' => $userId,
            'error' => $errorMessage,
        ]);

        PaymentFailed::dispatch($subscriptionId, $userId, $errorMessage);
    }

    private function handleChargeRefunded(array $data): void
    {
        $chargeData = $data['data']['object'] ?? [];

        $subscriptionId = (int) ($chargeData['metadata']['subscription_id'] ?? 0);
        $refundId = $chargeData['refunds']['data'][0]['id'] ?? ($chargeData['id'] ?? 'refund_' . uniqid());
        $amount = (string) (($chargeData['amount_refunded'] ?? 0) / 100);
        $currency = strtoupper($chargeData['currency'] ?? 'IDR');

        PaymentTransaction::create([
            'subscription_id' => $subscriptionId ?: null,
            'gateway_transaction_id' => $refundId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'refunded',
            'type' => 'refund',
            'metadata' => ['original_charge_id' => $chargeData['id'] ?? null],
        ]);

        Log::info('Payment refunded', [
            'refund_id' => $refundId,
            'amount' => $amount,
            'currency' => $currency,
            'subscription_id' => $subscriptionId,
        ]);

        PaymentRefunded::dispatch($subscriptionId, $refundId, $amount);
    }
}
