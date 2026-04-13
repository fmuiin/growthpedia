<?php

declare(strict_types=1);

namespace App\Modules\Admin\Contracts;

use App\Modules\Admin\DTOs\CreateMembershipPlanDTO;
use App\Modules\Admin\DTOs\UpdateMembershipPlanDTO;
use App\Modules\Subscription\DTOs\MembershipPlanDTO;
use App\Shared\Contracts\ServiceInterface;

interface MembershipPlanServiceInterface extends ServiceInterface
{
    public function createPlan(CreateMembershipPlanDTO $dto): MembershipPlanDTO;

    public function updatePlan(int $planId, UpdateMembershipPlanDTO $dto): MembershipPlanDTO;

    public function deactivatePlan(int $planId): MembershipPlanDTO;

    public function deletePlan(int $planId): void;
}
