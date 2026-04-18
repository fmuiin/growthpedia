<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\DTOs\PaymentRequestDTO;
use App\Modules\Payment\DTOs\PaymentResultDTO;
use App\Modules\Payment\DTOs\RefundResultDTO;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentRefunded;
use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Payment\Exceptions\PaymentGatewayException;
use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripePaymentGateway implements PaymentGatewayInterface
{
    private readonly string $apiKey;
    private readonly string $webhookSecret;
    private readonly string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.stripe.secret');
        $this->webhookSecret = (string) config('services.stripe.webhook_secret');
        $this->baseUrl = (string) config('services.stripe.base_url', 'https://api.stripe.com/v1');
    }

    public function charge(PaymentRequestDTO $request): PaymentResultDTO
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/payment_intents", [
                    'amount' => $this->toStripeAmount($request->amount),
                    'currency' => strtolower($request->currency),
                    'payment_method' => $request->token,
                    'confirm' => 'true',
                    'description' => $request->description,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $transactionId = $data['id'] ?? '';

                $this->logTransaction(
                    transactionId: $transactionId,
                    amount: $request->amount,
                    currency: $request->currency,
                    status: 'success',
                    type: 'charge',
                    metadata: ['description' => $request->description],
                );

                return new PaymentResultDTO(
                    success: true,
                    transactionId: $transactionId,
                    gatewaySubscriptionId: $data['id'] ?? null,
                    errorMessage: null,
                );
            }

            $errorMessage = $response->json('error.message', 'Payment failed.');

            $this->logTransaction(
                transactionId: $response->json('error.charge') ?? 'failed_' . uniqid(),
                amount: $request->amount,
                currency: $request->currency,
                status: 'failed',
                type: 'charge',
                metadata: ['error' => $errorMessage, 'description' => $request->description],
            );

            return new PaymentResultDTO(
                success: false,
                transactionId: null,
                gatewaySubscriptionId: null,
                errorMessage: $errorMessage,
            );
        } catch (\Throwable $e) {
            Log::error('Payment gateway charge error', [
                'message' => $e->getMessage(),
                'amount' => $request->amount,
                'currency' => $request->currency,
            ]);

            throw new PaymentGatewayException(
                'Payment gateway is unavailable: ' . $e->getMessage(),
                $e,
            );
        }
    }

    public function refund(string $transactionId, int $amount): RefundResultDTO
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/refunds", [
                    'payment_intent' => $transactionId,
                    'amount' => $amount,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $refundId = $data['id'] ?? '';

                $transaction = PaymentTransaction::where('gateway_transaction_id', $transactionId)->first();

                $this->logTransaction(
                    transactionId: $refundId,
                    amount: (string) ($amount / 100),
                    currency: $transaction->currency ?? 'IDR',
                    status: 'refunded',
                    type: 'refund',
                    metadata: ['original_transaction_id' => $transactionId],
                    subscriptionId: $transaction->subscription_id ?? null,
                );

                if ($transaction !== null) {
                    PaymentRefunded::dispatch(
                        $transaction->subscription_id,
                        $refundId,
                        (string) ($amount / 100),
                    );
                }

                return new RefundResultDTO(
                    success: true,
                    refundId: $refundId,
                    errorMessage: null,
                );
            }

            $errorMessage = $response->json('error.message', 'Refund failed.');

            Log::warning('Refund failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $errorMessage,
            ]);

            return new RefundResultDTO(
                success: false,
                refundId: null,
                errorMessage: $errorMessage,
            );
        } catch (\Throwable $e) {
            Log::error('Payment gateway refund error', [
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            throw new PaymentGatewayException(
                'Payment gateway is unavailable: ' . $e->getMessage(),
                $e,
            );
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function retryCharge(PaymentRequestDTO $request, int $maxRetries = 3): PaymentResultDTO
    {
        $lastResult = null;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            if ($attempt > 0) {
                $delayMicroseconds = (int) (pow(2, $attempt - 1) * 1_000_000);
                usleep($delayMicroseconds);
            }

            try {
                $result = $this->charge($request);

                if ($result->success) {
                    return $result;
                }

                $lastResult = $result;
            } catch (PaymentGatewayException $e) {
                Log::warning('Payment retry attempt failed', [
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                $lastResult = new PaymentResultDTO(
                    success: false,
                    transactionId: null,
                    gatewaySubscriptionId: null,
                    errorMessage: $e->getMessage(),
                );
            }
        }

        Log::error('All payment retry attempts exhausted', [
            'max_retries' => $maxRetries,
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);

        return $lastResult ?? new PaymentResultDTO(
            success: false,
            transactionId: null,
            gatewaySubscriptionId: null,
            errorMessage: 'All payment attempts failed.',
        );
    }

    /**
     * Create an HTTP client configured with TLS 1.2+ and Stripe authentication.
     */
    private function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withOptions([
            'curl' => [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ],
        ])->withToken($this->apiKey);
    }

    /**
     * Convert a decimal amount string to Stripe's integer (cents) format.
     */
    private function toStripeAmount(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    /**
     * Log a payment transaction to the database.
     */
    private function logTransaction(
        string $transactionId,
        string $amount,
        string $currency,
        string $status,
        string $type,
        ?array $metadata = null,
        ?int $subscriptionId = null,
    ): void {
        PaymentTransaction::create([
            'subscription_id' => $subscriptionId,
            'gateway_transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'type' => $type,
            'metadata' => $metadata,
        ]);
    }
}
