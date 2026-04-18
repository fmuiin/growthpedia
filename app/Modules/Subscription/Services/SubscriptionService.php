<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Services;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\DTOs\PaymentRequestDTO;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Modules\Subscription\DTOs\PaymentTokenDTO;
use App\Modules\Subscription\DTOs\SubscriptionDTO;
use App\Modules\Subscription\Events\SubscriptionActivated;
use App\Modules\Subscription\Events\SubscriptionSuspended;
use App\Modules\Subscription\Exceptions\PaymentFailedException;
use App\Modules\Subscription\Exceptions\PlanNotActiveException;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Carbon;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function subscribe(int $userId, int $planId, PaymentTokenDTO $token): SubscriptionDTO
    {
        $plan = MembershipPlan::find($planId);

        if ($plan === null) {
            throw new EntityNotFoundException('Membership plan not found.');
        }

        if (!$plan->is_active) {
            throw new PlanNotActiveException();
        }

        $paymentRequest = new PaymentRequestDTO(
            amount: $plan->price,
            currency: 'IDR',
            token: $token->token,
            description: "Subscription to {$plan->name}",
        );

        $result = $this->paymentGateway->charge($paymentRequest);

        if (!$result->success) {
            throw new PaymentFailedException($result->errorMessage ?? 'Payment processing failed.');
        }

        $now = Carbon::now();
        $endsAt = $this->calculateEndDate($now, $plan->billing_frequency);

        $subscription = Subscription::create([
            'user_id' => $userId,
            'membership_plan_id' => $planId,
            'status' => 'active',
            'starts_at' => $now,
            'ends_at' => $endsAt,
            'gateway_subscription_id' => $result->gatewaySubscriptionId,
        ]);

        if ($result->transactionId !== null) {
            PaymentTransaction::where('gateway_transaction_id', $result->transactionId)
                ->whereNull('subscription_id')
                ->update(['subscription_id' => $subscription->id]);
        }

        SubscriptionActivated::dispatch($subscription->id, $userId);

        return $this->toSubscriptionDTO($subscription);
    }

    public function handleRenewal(int $subscriptionId): SubscriptionDTO
    {
        $subscription = Subscription::with('membershipPlan')->find($subscriptionId);

        if ($subscription === null) {
            throw new EntityNotFoundException('Subscription not found.');
        }

        $plan = $subscription->membershipPlan;

        $paymentRequest = new PaymentRequestDTO(
            amount: $plan->price,
            currency: 'IDR',
            token: $subscription->gateway_subscription_id ?? '',
            description: "Renewal for {$plan->name}",
        );

        $result = $this->paymentGateway->retryCharge($paymentRequest);

        if ($result->success) {
            $newEndsAt = $this->calculateEndDate(
                Carbon::parse($subscription->ends_at),
                $plan->billing_frequency,
            );

            $subscription->update([
                'status' => 'active',
                'ends_at' => $newEndsAt,
                'grace_period_ends_at' => null,
            ]);

            if ($result->transactionId !== null) {
                PaymentTransaction::where('gateway_transaction_id', $result->transactionId)
                    ->whereNull('subscription_id')
                    ->update(['subscription_id' => $subscription->id]);
            }
        } else {
            $subscription->update([
                'status' => 'grace_period',
                'grace_period_ends_at' => Carbon::now()->addDays(7),
            ]);

            \App\Modules\Subscription\Events\PaymentFailed::dispatch(
                $subscription->id,
                $subscription->user_id,
            );
        }

        return $this->toSubscriptionDTO($subscription->refresh());
    }

    public function cancel(int $subscriptionId): void
    {
        $subscription = Subscription::find($subscriptionId);

        if ($subscription === null) {
            throw new EntityNotFoundException('Subscription not found.');
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);
    }

    public function suspendExpired(int $subscriptionId): void
    {
        $subscription = Subscription::find($subscriptionId);

        if ($subscription === null) {
            throw new EntityNotFoundException('Subscription not found.');
        }

        $subscription->update([
            'status' => 'suspended',
        ]);

        SubscriptionSuspended::dispatch($subscription->id, $subscription->user_id);
    }

    public function changePlan(int $subscriptionId, int $newPlanId): SubscriptionDTO
    {
        $subscription = Subscription::with('membershipPlan')->find($subscriptionId);

        if ($subscription === null) {
            throw new EntityNotFoundException('Subscription not found.');
        }

        $newPlan = MembershipPlan::find($newPlanId);

        if ($newPlan === null) {
            throw new EntityNotFoundException('Membership plan not found.');
        }

        if (!$newPlan->is_active) {
            throw new PlanNotActiveException();
        }

        $oldPlan = $subscription->membershipPlan;
        $now = Carbon::now();
        $endsAt = Carbon::parse($subscription->ends_at);
        $remainingDays = (int) $now->diffInDays($endsAt, false);

        if ($remainingDays < 0) {
            $remainingDays = 0;
        }

        $oldDaysInPeriod = $this->getDaysInPeriod($oldPlan->billing_frequency);
        $newDaysInPeriod = $this->getDaysInPeriod($newPlan->billing_frequency);

        $oldDailyRate = (float) $oldPlan->price / $oldDaysInPeriod;
        $newDailyRate = (float) $newPlan->price / $newDaysInPeriod;

        $proration = ($newDailyRate - $oldDailyRate) * $remainingDays;

        if ($proration > 0) {
            $paymentRequest = new PaymentRequestDTO(
                amount: (string) round($proration, 2),
                currency: 'IDR',
                token: $subscription->gateway_subscription_id ?? '',
                description: "Proration charge for plan change to {$newPlan->name}",
            );

            $result = $this->paymentGateway->charge($paymentRequest);

            if (!$result->success) {
                throw new PaymentFailedException($result->errorMessage ?? 'Proration payment failed.');
            }

            if ($result->transactionId !== null) {
                PaymentTransaction::where('gateway_transaction_id', $result->transactionId)
                    ->whereNull('subscription_id')
                    ->update(['subscription_id' => $subscription->id]);
            }
        }

        $newEndsAt = $this->calculateEndDate($now, $newPlan->billing_frequency);

        $subscription->update([
            'membership_plan_id' => $newPlanId,
            'ends_at' => $newEndsAt,
        ]);

        return $this->toSubscriptionDTO($subscription->refresh());
    }

    public function hasActiveSubscription(int $userId): bool
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'grace_period'])
            ->exists();
    }

    /**
     * @return int[]
     */
    public function getUserPlanCourseIds(int $userId): array
    {
        $subscription = Subscription::with('membershipPlan.courses')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'grace_period'])
            ->first();

        if ($subscription === null) {
            return [];
        }

        return $subscription->membershipPlan->courses->pluck('id')->all();
    }

    private function toSubscriptionDTO(Subscription $subscription): SubscriptionDTO
    {
        return new SubscriptionDTO(
            id: $subscription->id,
            userId: $subscription->user_id,
            membershipPlanId: $subscription->membership_plan_id,
            status: $subscription->status,
            startsAt: $subscription->starts_at,
            endsAt: $subscription->ends_at,
            gracePeriodEndsAt: $subscription->grace_period_ends_at,
            cancelledAt: $subscription->cancelled_at,
            gatewaySubscriptionId: $subscription->gateway_subscription_id,
            createdAt: $subscription->created_at,
        );
    }

    private function calculateEndDate(Carbon $from, string $billingFrequency): Carbon
    {
        return match ($billingFrequency) {
            'yearly' => $from->copy()->addYear(),
            default => $from->copy()->addMonth(),
        };
    }

    private function getDaysInPeriod(string $billingFrequency): int
    {
        return match ($billingFrequency) {
            'yearly' => 365,
            default => 30,
        };
    }
}
