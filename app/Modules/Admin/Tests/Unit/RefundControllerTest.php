<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests\Unit;

use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\DTOs\RefundResultDTO;
use App\Modules\Payment\Exceptions\PaymentGatewayException;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RefundControllerTest extends TestCase
{
    use RefreshDatabase;

    private $gatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
        $this->app->instance(PaymentGatewayInterface::class, $this->gatewayMock);
    }

    private function createSubscriptionWithTransaction(): array
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Test Plan',
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
            'gateway_subscription_id' => 'sub_gw_123',
        ]);
        $transaction = PaymentTransaction::create([
            'subscription_id' => $subscription->id,
            'gateway_transaction_id' => 'pi_txn_123',
            'amount' => '100.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);

        return [$user, $subscription, $transaction];
    }

    public function test_refund_succeeds_and_cancels_subscription(): void
    {
        [$user, $subscription, $transaction] = $this->createSubscriptionWithTransaction();

        $this->gatewayMock->shouldReceive('refund')
            ->once()
            ->with('pi_txn_123', 10000)
            ->andReturn(new RefundResultDTO(
                success: true,
                refundId: 're_123',
                errorMessage: null,
            ));

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.subscriptions.refund', ['subscription' => $subscription->id]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_refund_with_custom_amount(): void
    {
        [$user, $subscription, $transaction] = $this->createSubscriptionWithTransaction();

        $this->gatewayMock->shouldReceive('refund')
            ->once()
            ->with('pi_txn_123', 5000)
            ->andReturn(new RefundResultDTO(
                success: true,
                refundId: 're_partial',
                errorMessage: null,
            ));

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.subscriptions.refund', ['subscription' => $subscription->id]),
            ['amount' => 50.00, 'reason' => 'Partial refund requested'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_refund_fails_when_subscription_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.subscriptions.refund', ['subscription' => 99999]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Subscription not found.');
    }

    public function test_refund_fails_when_no_successful_transaction(): void
    {
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

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.subscriptions.refund', ['subscription' => $subscription->id]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No successful payment transaction found for this subscription.');
    }

    public function test_refund_handles_gateway_failure(): void
    {
        [$user, $subscription, $transaction] = $this->createSubscriptionWithTransaction();

        $this->gatewayMock->shouldReceive('refund')
            ->once()
            ->andReturn(new RefundResultDTO(
                success: false,
                refundId: null,
                errorMessage: 'Charge already refunded',
            ));

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.subscriptions.refund', ['subscription' => $subscription->id]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Refund failed: Charge already refunded');

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
    }

    public function test_refund_handles_gateway_exception(): void
    {
        [$user, $subscription, $transaction] = $this->createSubscriptionWithTransaction();

        $this->gatewayMock->shouldReceive('refund')
            ->once()
            ->andThrow(new PaymentGatewayException('Gateway unavailable'));

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.subscriptions.refund', ['subscription' => $subscription->id]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
    }
}
