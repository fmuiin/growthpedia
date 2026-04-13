<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscription\Contracts\SubscriptionServiceInterface;
use App\Modules\Subscription\DTOs\PaymentTokenDTO;
use App\Modules\Subscription\Exceptions\PaymentFailedException;
use App\Modules\Subscription\Exceptions\PlanNotActiveException;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use App\Modules\Subscription\Requests\ChangePlanRequest;
use App\Modules\Subscription\Requests\SubscribeRequest;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function plans(): Response
    {
        $plans = MembershipPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
        ]);
    }

    public function checkout(int $planId): Response|RedirectResponse
    {
        $plan = MembershipPlan::where('is_active', true)->find($planId);

        if ($plan === null) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Membership plan not found or is no longer active.');
        }

        return Inertia::render('Subscription/Checkout', [
            'plan' => $plan,
        ]);
    }

    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $token = new PaymentTokenDTO(
                token: $request->validated('payment_token'),
                gatewayType: 'default',
            );

            $this->subscriptionService->subscribe(
                userId: $user->id,
                planId: (int) $request->validated('plan_id'),
                token: $token,
            );
        } catch (PlanNotActiveException $e) {
            return redirect()->route('subscription.plans')
                ->with('error', $e->getMessage());
        } catch (PaymentFailedException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (EntityNotFoundException $e) {
            return redirect()->route('subscription.plans')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('subscription.manage')
            ->with('success', 'Subscription activated successfully.');
    }

    public function manage(): Response
    {
        $user = Auth::user();

        $subscription = Subscription::with('membershipPlan')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'grace_period'])
            ->latest()
            ->first();

        $plans = MembershipPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return Inertia::render('Subscription/ManageSubscription', [
            'subscription' => $subscription,
            'plans' => $plans,
        ]);
    }

    public function cancel(int $subscriptionId): RedirectResponse
    {
        try {
            $this->subscriptionService->cancel($subscriptionId);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', 'Subscription cancelled. Access continues until the end of your billing period.');
    }

    public function changePlan(int $subscriptionId, ChangePlanRequest $request): RedirectResponse
    {
        try {
            $this->subscriptionService->changePlan(
                subscriptionId: $subscriptionId,
                newPlanId: (int) $request->validated('new_plan_id'),
            );
        } catch (PlanNotActiveException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (PaymentFailedException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (EntityNotFoundException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', 'Subscription plan changed successfully.');
    }
}
