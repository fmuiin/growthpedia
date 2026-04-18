<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Tests\Unit;

use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\DTOs\PaymentRequestDTO;
use App\Modules\Payment\DTOs\PaymentResultDTO;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Subscription\DTOs\PaymentTokenDTO;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class SubscriptionPaymentWiringTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;
    private PaymentGatewayInterface $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = Mockery::mock(PaymentGatewayInterface::class);
        $this->service = new SubscriptionService($this->gateway);
    }

    public function test_subscribe_links_payment_transaction_with_subscription_id(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Monthly Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // Simulate what StripePaymentGateway does: create a PaymentTransaction without subscription_id
        PaymentTransaction::create([
            'subscription_id' => null,
            'gateway_transaction_id' => 'txn_sub_123',
            'amount' => '100.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);

        $this->gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResultDTO(
                success: true,
                transactionId: 'txn_sub_123',
                gatewaySubscriptionId: 'sub_gw_123',
                errorMessage: null,
            ));

        $token = new PaymentTokenDTO(token: 'tok_abc', gatewayType: 'stripe');
        $result = $this->service->subscribe($user->id, $plan->id, $token);

        $transaction = PaymentTransaction::where('gateway_transaction_id', 'txn_sub_123')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($result->id, $transaction->subscription_id);
    }

    public function test_handle_renewal_links_payment_transaction_with_subscription_id(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-01'));

        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Monthly',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-05-01'),
            'ends_at' => Carbon::parse('2024-06-01'),
            'gateway_subscription_id' => 'sub_gw',
        ]);

        // Simulate gateway creating a transaction without subscription_id
        PaymentTransaction::create([
            'subscription_id' => null,
            'gateway_transaction_id' => 'txn_renewal_456',
            'amount' => '100.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);

        $this->gateway->shouldReceive('retryCharge')
            ->once()
            ->andReturn(new PaymentResultDTO(
                success: true,
                transactionId: 'txn_renewal_456',
                gatewaySubscriptionId: null,
                errorMessage: null,
            ));

        $result = $this->service->handleRenewal($subscription->id);

        $transaction = PaymentTransaction::where('gateway_transaction_id', 'txn_renewal_456')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($subscription->id, $transaction->subscription_id);
    }

    public function test_change_plan_links_proration_transaction_with_subscription_id(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-15'));

        $user = User::factory()->create();
        $oldPlan = MembershipPlan::create([
            'name' => 'Basic',
            'price' => '90.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $newPlan = MembershipPlan::create([
            'name' => 'Pro',
            'price' => '150.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $oldPlan->id,
            'status' => 'active',
            'starts_at' => Carbon::parse('2024-06-01'),
            'ends_at' => Carbon::parse('2024-06-30'),
            'gateway_subscription_id' => 'sub_gw',
        ]);

        // Simulate gateway creating a proration transaction without subscription_id
        PaymentTransaction::create([
            'subscription_id' => null,
            'gateway_transaction_id' => 'txn_prorate_789',
            'amount' => '30.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);

        $this->gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResultDTO(
                success: true,
                transactionId: 'txn_prorate_789',
                gatewaySubscriptionId: null,
                errorMessage: null,
            ));

        $this->service->changePlan($subscription->id, $newPlan->id);

        $transaction = PaymentTransaction::where('gateway_transaction_id', 'txn_prorate_789')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($subscription->id, $transaction->subscription_id);
    }

    public function test_subscribe_does_not_fail_when_no_transaction_record_exists(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResultDTO(
                success: true,
                transactionId: 'txn_no_record',
                gatewaySubscriptionId: 'sub_gw',
                errorMessage: null,
            ));

        $token = new PaymentTokenDTO(token: 'tok', gatewayType: 'stripe');
        $result = $this->service->subscribe($user->id, $plan->id, $token);

        // Should not throw — gracefully handles missing transaction record
        $this->assertEquals('active', $result->status);
    }
}
