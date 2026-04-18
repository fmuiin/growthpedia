<?php

declare(strict_types=1);

namespace App\Modules\Payment\Tests\Unit;

use App\Models\User;
use App\Modules\Payment\DTOs\PaymentRequestDTO;
use App\Modules\Payment\DTOs\PaymentResultDTO;
use App\Modules\Payment\DTOs\RefundResultDTO;
use App\Modules\Payment\Events\PaymentRefunded;
use App\Modules\Payment\Exceptions\PaymentGatewayException;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Payment\Services\StripePaymentGateway;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private StripePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_fake',
            'services.stripe.webhook_secret' => 'whsec_test_secret',
            'services.stripe.base_url' => 'https://api.stripe.com/v1',
        ]);

        $this->gateway = new StripePaymentGateway();
    }

    // --- charge() ---

    public function test_charge_returns_success_on_successful_payment(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_test_123',
                'status' => 'succeeded',
            ], 200),
        ]);

        $request = new PaymentRequestDTO(
            amount: '100.00',
            currency: 'IDR',
            token: 'pm_tok_visa',
            description: 'Test charge',
        );

        $result = $this->gateway->charge($request);

        $this->assertInstanceOf(PaymentResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('pi_test_123', $result->transactionId);
        $this->assertNull($result->errorMessage);

        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => 'pi_test_123',
            'amount' => '100.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);
    }

    public function test_charge_returns_failure_on_declined_payment(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'error' => [
                    'message' => 'Your card was declined.',
                    'type' => 'card_error',
                ],
            ], 402),
        ]);

        $request = new PaymentRequestDTO(
            amount: '50.00',
            currency: 'IDR',
            token: 'pm_tok_declined',
            description: 'Declined charge',
        );

        $result = $this->gateway->charge($request);

        $this->assertFalse($result->success);
        $this->assertNull($result->transactionId);
        $this->assertEquals('Your card was declined.', $result->errorMessage);

        $this->assertDatabaseHas('payment_transactions', [
            'status' => 'failed',
            'type' => 'charge',
        ]);
    }

    public function test_charge_throws_gateway_exception_on_connection_error(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $request = new PaymentRequestDTO(
            amount: '100.00',
            currency: 'IDR',
            token: 'pm_tok_visa',
            description: 'Test charge',
        );

        $this->expectException(PaymentGatewayException::class);
        $this->gateway->charge($request);
    }

    public function test_charge_uses_tokenized_payment_method_not_raw_card(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_test_tok',
            ], 200),
        ]);

        $request = new PaymentRequestDTO(
            amount: '200.00',
            currency: 'IDR',
            token: 'pm_tokenized_method',
            description: 'Tokenized payment',
        );

        $this->gateway->charge($request);

        Http::assertSent(function ($httpRequest) {
            $body = $httpRequest->body();
            // Verify token is sent as payment_method, not raw card data
            $this->assertStringContainsString('payment_method=pm_tokenized_method', $body);
            $this->assertStringNotContainsString('card_number', $body);
            $this->assertStringNotContainsString('cvv', $body);
            $this->assertStringNotContainsString('cvc', $body);
            return true;
        });
    }

    // --- refund() ---

    public function test_refund_returns_success_on_successful_refund(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Create an existing transaction to look up
        PaymentTransaction::create([
            'subscription_id' => $subscription->id,
            'gateway_transaction_id' => 'pi_original',
            'amount' => '100.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);

        Http::fake([
            'api.stripe.com/v1/refunds' => Http::response([
                'id' => 're_test_456',
                'status' => 'succeeded',
            ], 200),
        ]);

        $result = $this->gateway->refund('pi_original', 10000);

        $this->assertInstanceOf(RefundResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('re_test_456', $result->refundId);
        $this->assertNull($result->errorMessage);

        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => 're_test_456',
            'status' => 'refunded',
            'type' => 'refund',
        ]);

        Event::assertDispatched(PaymentRefunded::class, function ($event) use ($subscription) {
            return $event->subscriptionId === $subscription->id
                && $event->refundId === 're_test_456';
        });
    }

    public function test_refund_returns_failure_on_failed_refund(): void
    {
        Http::fake([
            'api.stripe.com/v1/refunds' => Http::response([
                'error' => [
                    'message' => 'Charge has already been refunded.',
                ],
            ], 400),
        ]);

        $result = $this->gateway->refund('pi_already_refunded', 5000);

        $this->assertFalse($result->success);
        $this->assertNull($result->refundId);
        $this->assertEquals('Charge has already been refunded.', $result->errorMessage);
    }

    // --- verifyWebhookSignature() ---

    public function test_verify_webhook_signature_returns_true_for_valid_signature(): void
    {
        $payload = '{"type":"payment_intent.succeeded","data":{}}';
        $expectedSignature = hash_hmac('sha256', $payload, 'whsec_test_secret');

        $this->assertTrue($this->gateway->verifyWebhookSignature($payload, $expectedSignature));
    }

    public function test_verify_webhook_signature_returns_false_for_invalid_signature(): void
    {
        $payload = '{"type":"payment_intent.succeeded","data":{}}';

        $this->assertFalse($this->gateway->verifyWebhookSignature($payload, 'invalid_signature'));
    }

    public function test_verify_webhook_signature_returns_false_for_tampered_payload(): void
    {
        $originalPayload = '{"type":"payment_intent.succeeded","data":{}}';
        $tamperedPayload = '{"type":"payment_intent.succeeded","data":{"tampered":true}}';
        $signature = hash_hmac('sha256', $originalPayload, 'whsec_test_secret');

        $this->assertFalse($this->gateway->verifyWebhookSignature($tamperedPayload, $signature));
    }

    // --- retryCharge() ---

    public function test_retry_charge_succeeds_on_first_attempt(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_retry_1',
            ], 200),
        ]);

        $request = new PaymentRequestDTO(
            amount: '100.00',
            currency: 'IDR',
            token: 'pm_tok_visa',
            description: 'Retry test',
        );

        $result = $this->gateway->retryCharge($request, 3);

        $this->assertTrue($result->success);
        $this->assertEquals('pi_retry_1', $result->transactionId);
    }

    public function test_retry_charge_succeeds_on_second_attempt(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'Temporary failure']], 500)
            ->push(['id' => 'pi_retry_2'], 200);

        $request = new PaymentRequestDTO(
            amount: '100.00',
            currency: 'IDR',
            token: 'pm_tok_visa',
            description: 'Retry test',
        );

        $result = $this->gateway->retryCharge($request, 3);

        // The first attempt throws PaymentGatewayException (500 triggers connection-level error handling),
        // but the second attempt succeeds
        // Note: Http::fake with 500 status returns a response (not exception), so charge() returns failed result
        // Then second attempt succeeds
        $this->assertTrue($result->success);
        $this->assertEquals('pi_retry_2', $result->transactionId);
    }

    public function test_retry_charge_returns_failure_after_all_retries_exhausted(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'error' => ['message' => 'Card declined'],
            ], 402),
        ]);

        $request = new PaymentRequestDTO(
            amount: '100.00',
            currency: 'IDR',
            token: 'pm_tok_declined',
            description: 'All retries fail',
        );

        $result = $this->gateway->retryCharge($request, 3);

        $this->assertFalse($result->success);
        $this->assertEquals('Card declined', $result->errorMessage);
    }

    public function test_retry_charge_respects_max_retries_parameter(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'error' => ['message' => 'Declined'],
            ], 402),
        ]);

        $request = new PaymentRequestDTO(
            amount: '100.00',
            currency: 'IDR',
            token: 'pm_tok_declined',
            description: 'Max retries test',
        );

        $this->gateway->retryCharge($request, 2);

        // Should have made exactly 2 HTTP requests (2 retries)
        Http::assertSentCount(2);
    }

    // --- Transaction logging ---

    public function test_charge_logs_transaction_with_all_required_fields(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_log_test',
            ], 200),
        ]);

        $request = new PaymentRequestDTO(
            amount: '250.50',
            currency: 'IDR',
            token: 'pm_tok_visa',
            description: 'Logging test',
        );

        $this->gateway->charge($request);

        $transaction = PaymentTransaction::where('gateway_transaction_id', 'pi_log_test')->first();

        $this->assertNotNull($transaction);
        $this->assertEquals('pi_log_test', $transaction->gateway_transaction_id);
        $this->assertEquals('250.50', $transaction->amount);
        $this->assertEquals('IDR', $transaction->currency);
        $this->assertEquals('success', $transaction->status);
        $this->assertEquals('charge', $transaction->type);
        $this->assertNotNull($transaction->created_at);
    }
}
