<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Contracts;

use App\Modules\Subscription\DTOs\PaymentTokenDTO;
use App\Modules\Subscription\DTOs\SubscriptionDTO;
use App\Shared\Contracts\ServiceInterface;

interface SubscriptionServiceInterface extends ServiceInterface
{
    public function subscribe(int $userId, int $planId, PaymentTokenDTO $token): SubscriptionDTO;

    public function cancel(int $subscriptionId): void;

    public function changePlan(int $subscriptionId, int $newPlanId): SubscriptionDTO;

    public function hasActiveSubscription(int $userId): bool;

    /**
     * @return int[]
     */
    public function getUserPlanCourseIds(int $userId): array;

    public function handleRenewal(int $subscriptionId): SubscriptionDTO;

    public function suspendExpired(int $subscriptionId): void;
}
