<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Tests\Unit;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\DTOs\PaymentRequestDTO;
use App\Modules\Payment\DTOs\PaymentResultDTO;
use App\Modules\Subscription\DTOs\PaymentTokenDTO;
use App\Modules\Subscription\DTOs\SubscriptionDTO;
use App\Modules\Subscription\Events\PaymentFailed;
use App\Modules\Subscription\Events\SubscriptionActivated;
use App\Modules\Subscription\Events\SubscriptionSuspended;
use App\Modules\Subscription\Exceptions\PaymentFailedException;
use App\Modules\Subscription\Exceptions\PlanNotActiveException;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use App\Modules\Subscription\Services\SubscriptionService;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
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

    // --- subscribe() ---

    public function test_subscribe_creates_active_subscription_on_payment_success(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-01 12:00:00'));

        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Monthly Plan',
            'description' => 'Test',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->gateway->shouldReceive('charge')
            ->once()
            ->withArgs(fn (PaymentRequestDTO $req) => $req->amount === '100.00' && $req->currency === 'IDR')
            ->andReturn(new PaymentResultDTO(
                success: true,
                transactionId: 'txn_123',
                gatewaySubscriptionId: 'sub_gw_123',
                errorMessage: null,
            ));

        $token = new PaymentTokenDTO(token: 'tok_abc', gatewayType: 'stripe');
        $result = $this->service->subscribe($user->id, $plan->id, $token);

        $this->assertInstanceOf(SubscriptionDTO::class, $result);
        $this->assertEquals($user->id, $result->userId);
        $this->assertEquals($plan->id, $result->membershipPlanId);
        $this->assertEquals('active', $result->status);
        $this->assertEquals('sub_gw_123', $result->gatewaySubscriptionId);
        $this->assertEquals('2024-07-01 12:00:00', $result->endsAt->format('Y-m-d H:i:s'));

        Event::assertDispatched(SubscriptionActivated::class, fn ($e) => $e->userId === $user->id);
    }

    public function test_subscribe_yearly_plan_sets_ends_at_one_year_later(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-01-15 10:00:00'));

        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Yearly Plan',
            'price' => '999.00',
            'billing_frequency' => 'yearly',
            'is_active' => true,
        ]);

        $this->gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResultDTO(true, 'txn_y', 'sub_y', null));

        $token = new PaymentTokenDTO(token: 'tok_y', gatewayType: 'stripe');
        $result = $this->service->subscribe($user->id, $plan->id, $token);

        $this->assertEquals('2025-01-15 10:00:00', $result->endsAt->format('Y-m-d H:i:s'));
    }

    public function test_subscribe_throws_when_plan_not_found(): void
    {
        $user = User::factory()->create();
        $token = new PaymentTokenDTO(token: 'tok', gatewayType: 'stripe');

        $this->expectException(EntityNotFoundException::class);
        $this->service->subscribe($user->id, 999, $token);
    }

    public function test_subscribe_throws_when_plan_not_active(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Inactive',
            'price' => '50.00',
            'billing_frequency' => 'monthly',
            'is_active' => false,
        ]);

        $token = new PaymentTokenDTO(token: 'tok', gatewayType: 'stripe');

        $this->expectException(PlanNotActiveException::class);
        $this->service->subscribe($user->id, $plan->id, $token);
    }

    public function test_subscribe_throws_on_payment_failure(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResultDTO(false, null, null, 'Card declined'));

        $token = new PaymentTokenDTO(token: 'tok', gatewayType: 'stripe');

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Card declined');
        $this->service->subscribe($user->id, $plan->id, $token);
    }

    // --- handleRenewal() ---

    public function test_handle_renewal_extends_period_on_success(): void
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

        $this->gateway->shouldReceive('retryCharge')
            ->once()
            ->andReturn(new PaymentResultDTO(true, 'txn_r', null, null));

        $result = $this->service->handleRenewal($subscription->id);

        $this->assertEquals('active', $result->status);
        $this->assertEquals('2024-07-01', Carbon::parse($result->endsAt)->format('Y-m-d'));
        $this->assertNull($result->gracePeriodEndsAt);
    }

    public function test_handle_renewal_sets_grace_period_on_failure(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-01 12:00:00'));

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

        $this->gateway->shouldReceive('retryCharge')
            ->once()
            ->andReturn(new PaymentResultDTO(false, null, null, 'Insufficient funds'));

        $result = $this->service->handleRenewal($subscription->id);

        $this->assertEquals('grace_period', $result->status);
        $this->assertEquals('2024-06-08 12:00:00', $result->gracePeriodEndsAt->format('Y-m-d H:i:s'));

        Event::assertDispatched(PaymentFailed::class, fn ($e) => $e->subscriptionId === $subscription->id);
    }

    public function test_handle_renewal_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->handleRenewal(999);
    }

    // --- cancel() ---

    public function test_cancel_sets_status_and_cancelled_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-15 10:00:00'));

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
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(15),
        ]);

        $this->service->cancel($subscription->id);

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertEquals('2024-06-15 10:00:00', $subscription->cancelled_at->format('Y-m-d H:i:s'));
    }

    public function test_cancel_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->cancel(999);
    }

    // --- suspendExpired() ---

    public function test_suspend_expired_sets_status_and_dispatches_event(): void
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
            'status' => 'grace_period',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subWeek(),
            'grace_period_ends_at' => now()->subDay(),
        ]);

        $this->service->suspendExpired($subscription->id);

        $subscription->refresh();
        $this->assertEquals('suspended', $subscription->status);

        Event::assertDispatched(SubscriptionSuspended::class, fn ($e) => $e->subscriptionId === $subscription->id && $e->userId === $user->id);
    }

    public function test_suspend_expired_throws_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->suspendExpired(999);
    }

    // --- changePlan() ---

    public function test_change_plan_charges_proration_on_upgrade(): void
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

        // remaining_days = 15, old_daily = 90/30 = 3, new_daily = 150/30 = 5
        // proration = (5 - 3) * 15 = 30
        $this->gateway->shouldReceive('charge')
            ->once()
            ->withArgs(fn (PaymentRequestDTO $req) => (float) $req->amount === 30.0)
            ->andReturn(new PaymentResultDTO(true, 'txn_pro', null, null));

        $result = $this->service->changePlan($subscription->id, $newPlan->id);

        $this->assertEquals($newPlan->id, $result->membershipPlanId);
        $this->assertEquals('2024-07-15', Carbon::parse($result->endsAt)->format('Y-m-d'));
    }

    public function test_change_plan_does_not_charge_on_downgrade(): void
    {
        Event::fake();
        Carbon::setTestNow(Carbon::parse('2024-06-15'));

        $user = User::factory()->create();
        $oldPlan = MembershipPlan::create([
            'name' => 'Pro',
            'price' => '150.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $newPlan = MembershipPlan::create([
            'name' => 'Basic',
            'price' => '90.00',
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

        // proration = (3 - 5) * 15 = -30, no charge
        $this->gateway->shouldNotReceive('charge');

        $result = $this->service->changePlan($subscription->id, $newPlan->id);

        $this->assertEquals($newPlan->id, $result->membershipPlanId);
    }

    public function test_change_plan_throws_when_subscription_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->changePlan(999, 1);
    }

    public function test_change_plan_throws_when_new_plan_not_found(): void
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

        $this->expectException(EntityNotFoundException::class);
        $this->service->changePlan($subscription->id, 999);
    }

    public function test_change_plan_throws_when_new_plan_not_active(): void
    {
        $user = User::factory()->create();
        $oldPlan = MembershipPlan::create([
            'name' => 'Old',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $newPlan = MembershipPlan::create([
            'name' => 'Inactive',
            'price' => '200.00',
            'billing_frequency' => 'monthly',
            'is_active' => false,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $oldPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->expectException(PlanNotActiveException::class);
        $this->service->changePlan($subscription->id, $newPlan->id);
    }

    // --- hasActiveSubscription() ---

    public function test_has_active_subscription_returns_true_for_active(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($this->service->hasActiveSubscription($user->id));
    }

    public function test_has_active_subscription_returns_true_for_grace_period(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'grace_period',
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
            'grace_period_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($this->service->hasActiveSubscription($user->id));
    }

    public function test_has_active_subscription_returns_false_for_cancelled(): void
    {
        $user = User::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'cancelled',
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
            'cancelled_at' => now()->subWeek(),
        ]);

        $this->assertFalse($this->service->hasActiveSubscription($user->id));
    }

    public function test_has_active_subscription_returns_false_for_no_subscription(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->service->hasActiveSubscription($user->id));
    }

    // --- getUserPlanCourseIds() ---

    public function test_get_user_plan_course_ids_returns_course_ids(): void
    {
        $user = User::factory()->create();
        $instructor = User::factory()->create();
        $course1 = Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Course 1',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);
        $course2 = Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Course 2',
            'description' => 'Desc',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $plan->courses()->sync([$course1->id, $course2->id]);

        Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $courseIds = $this->service->getUserPlanCourseIds($user->id);

        $this->assertCount(2, $courseIds);
        $this->assertContains($course1->id, $courseIds);
        $this->assertContains($course2->id, $courseIds);
    }

    public function test_get_user_plan_course_ids_returns_empty_for_no_subscription(): void
    {
        $user = User::factory()->create();
        $this->assertEquals([], $this->service->getUserPlanCourseIds($user->id));
    }
}
