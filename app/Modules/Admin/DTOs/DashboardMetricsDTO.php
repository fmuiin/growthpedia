<?php

declare(strict_types=1);

namespace App\Modules\Admin\DTOs;

use App\Shared\DTOs\BaseDTO;

readonly class DashboardMetricsDTO extends BaseDTO
{
    public function __construct(
        public int $totalLearnerCount,
        public int $activeSubscriptionCount,
        public int $totalCourseCount,
        public string $totalRevenue,
    ) {}
}
