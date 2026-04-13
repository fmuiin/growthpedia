<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Contracts\MembershipPlanServiceInterface;
use App\Modules\Admin\DTOs\CreateMembershipPlanDTO;
use App\Modules\Admin\DTOs\UpdateMembershipPlanDTO;
use App\Modules\Admin\Exceptions\PlanHasActiveSubscriptionsException;
use App\Modules\Subscription\DTOs\MembershipPlanDTO;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Shared\Exceptions\EntityNotFoundException;

class MembershipPlanService implements MembershipPlanServiceInterface
{
    public function createPlan(CreateMembershipPlanDTO $dto): MembershipPlanDTO
    {
        $plan = MembershipPlan::create([
            'name' => $dto->name,
            'description' => $dto->description,
            'price' => $dto->price,
            'billing_frequency' => $dto->billingFrequency,
            'is_active' => true,
        ]);

        if ($dto->courseIds !== []) {
            $plan->courses()->sync($dto->courseIds);
        }

        return $this->toMembershipPlanDTO($plan->refresh());
    }

    public function updatePlan(int $planId, UpdateMembershipPlanDTO $dto): MembershipPlanDTO
    {
        $plan = MembershipPlan::find($planId);

        if ($plan === null) {
            throw new EntityNotFoundException("Membership plan not found.");
        }

        $fields = [];

        if ($dto->name !== null) {
            $fields['name'] = $dto->name;
        }
        if ($dto->description !== null) {
            $fields['description'] = $dto->description;
        }
        if ($dto->price !== null) {
            $fields['price'] = $dto->price;
        }
        if ($dto->billingFrequency !== null) {
            $fields['billing_frequency'] = $dto->billingFrequency;
        }

        if ($fields !== []) {
            $plan->update($fields);
        }

        if ($dto->courseIds !== null) {
            $plan->courses()->sync($dto->courseIds);
        }

        return $this->toMembershipPlanDTO($plan->refresh());
    }

    public function deactivatePlan(int $planId): MembershipPlanDTO
    {
        $plan = MembershipPlan::find($planId);

        if ($plan === null) {
            throw new EntityNotFoundException("Membership plan not found.");
        }

        $plan->update(['is_active' => false]);

        return $this->toMembershipPlanDTO($plan->refresh());
    }

    public function deletePlan(int $planId): void
    {
        $plan = MembershipPlan::find($planId);

        if ($plan === null) {
            throw new EntityNotFoundException("Membership plan not found.");
        }

        $activeCount = $plan->subscriptions()
            ->whereIn('status', ['active', 'grace_period'])
            ->count();

        if ($activeCount > 0) {
            throw new PlanHasActiveSubscriptionsException($activeCount);
        }

        $plan->courses()->detach();
        $plan->delete();
    }

    private function toMembershipPlanDTO(MembershipPlan $plan): MembershipPlanDTO
    {
        return new MembershipPlanDTO(
            id: $plan->id,
            name: $plan->name,
            description: $plan->description,
            price: $plan->price,
            billingFrequency: $plan->billing_frequency,
            isActive: $plan->is_active,
            createdAt: $plan->created_at,
        );
    }
}
