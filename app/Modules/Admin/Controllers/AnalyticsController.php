<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Contracts\AnalyticsServiceInterface;
use App\Shared\DTOs\DateRangeDTO;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsServiceInterface $analyticsService,
    ) {}

    /**
     * Display dashboard metrics for the selected date range.
     *
     * Validates: Requirements 9.1, 9.3
     */
    public function dashboard(Request $request): Response
    {
        $range = $this->buildDateRange($request);
        $metrics = $this->analyticsService->getDashboardMetrics($range);

        return Inertia::render('Admin/Analytics', [
            'metrics' => $metrics->toArray(),
            'startDate' => $range->startDate->toDateString(),
            'endDate' => $range->endDate->toDateString(),
        ]);
    }

    /**
     * Display course analytics for a specific course.
     *
     * Validates: Requirement 9.2
     */
    public function courseAnalytics(int $courseId): Response
    {
        $analytics = $this->analyticsService->getCourseAnalytics($courseId);

        return Inertia::render('Admin/CourseAnalytics', [
            'analytics' => $analytics->toArray(),
        ]);
    }

    /**
     * Export analytics data as CSV.
     *
     * Validates: Requirement 9.4
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $range = $this->buildDateRange($request);

        return $this->analyticsService->exportCsv($range);
    }

    /**
     * Display paginated list of flagged comments.
     *
     * Validates: Requirement 9.5
     */
    public function flaggedComments(Request $request): Response
    {
        $page = (int) $request->query('page', '1');
        $result = $this->analyticsService->getFlaggedComments($page);

        return Inertia::render('Admin/FlaggedComments', [
            'flaggedComments' => $result->toArray(),
        ]);
    }

    /**
     * Build a DateRangeDTO from request query parameters.
     * Defaults to the last 30 days if no dates are provided.
     */
    private function buildDateRange(Request $request): DateRangeDTO
    {
        $startDate = $request->query('start_date')
            ? CarbonImmutable::parse($request->query('start_date'))->startOfDay()
            : CarbonImmutable::now()->subDays(30)->startOfDay();

        $endDate = $request->query('end_date')
            ? CarbonImmutable::parse($request->query('end_date'))->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        return new DateRangeDTO(
            startDate: $startDate,
            endDate: $endDate,
        );
    }
}
