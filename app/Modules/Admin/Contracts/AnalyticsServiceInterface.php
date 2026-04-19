<?php

declare(strict_types=1);

namespace App\Modules\Admin\Contracts;

use App\Modules\Admin\DTOs\CourseAnalyticsDTO;
use App\Modules\Admin\DTOs\DashboardMetricsDTO;
use App\Modules\Admin\DTOs\PaginatedFlaggedCommentsDTO;
use App\Shared\Contracts\ServiceInterface;
use App\Shared\DTOs\DateRangeDTO;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface AnalyticsServiceInterface extends ServiceInterface
{
    public function getDashboardMetrics(DateRangeDTO $range): DashboardMetricsDTO;

    public function getCourseAnalytics(int $courseId): CourseAnalyticsDTO;

    public function exportCsv(DateRangeDTO $range): StreamedResponse;

    public function getFlaggedComments(int $page): PaginatedFlaggedCommentsDTO;
}
