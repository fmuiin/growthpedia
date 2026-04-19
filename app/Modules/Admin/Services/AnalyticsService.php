<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Contracts\AnalyticsServiceInterface;
use App\Modules\Admin\DTOs\CourseAnalyticsDTO;
use App\Modules\Admin\DTOs\DashboardMetricsDTO;
use App\Modules\Admin\DTOs\FlaggedCommentDTO;
use App\Modules\Admin\DTOs\PaginatedFlaggedCommentsDTO;
use App\Modules\Course\Models\Course;
use App\Modules\Discussion\Models\Comment;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Progress\Models\Enrollment;
use App\Modules\Subscription\Models\Subscription;
use App\Shared\DTOs\DateRangeDTO;
use App\Shared\Exceptions\EntityNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService implements AnalyticsServiceInterface
{
    private const int FLAGGED_COMMENTS_PER_PAGE = 15;

    /**
     * Get dashboard metrics: total learner count, active subscription count,
     * total course count, and total revenue for the given date range.
     *
     * Validates: Requirement 9.1
     */
    public function getDashboardMetrics(DateRangeDTO $range): DashboardMetricsDTO
    {
        $totalLearnerCount = User::where('role', 'learner')->count();

        $activeSubscriptionCount = Subscription::where('status', 'active')->count();

        $totalCourseCount = Course::count();

        $totalRevenue = PaymentTransaction::where('status', 'success')
            ->whereBetween('created_at', [$range->startDate, $range->endDate])
            ->sum('amount');

        return new DashboardMetricsDTO(
            totalLearnerCount: $totalLearnerCount,
            activeSubscriptionCount: $activeSubscriptionCount,
            totalCourseCount: $totalCourseCount,
            totalRevenue: number_format((float) $totalRevenue, 2, '.', ''),
        );
    }

    /**
     * Get course analytics: enrollment count, average completion percentage,
     * and average rating for a specific course.
     *
     * Validates: Requirement 9.2
     */
    public function getCourseAnalytics(int $courseId): CourseAnalyticsDTO
    {
        $course = Course::find($courseId);

        if ($course === null) {
            throw new EntityNotFoundException("Course not found.");
        }

        $enrollments = Enrollment::where('course_id', $courseId);

        $enrollmentCount = $enrollments->count();

        $averageCompletion = $enrollmentCount > 0
            ? (float) Enrollment::where('course_id', $courseId)->avg('completion_percentage')
            : 0.0;

        // Average rating — currently no ratings table exists in the schema,
        // so we return null. This can be wired when a ratings feature is added.
        $averageRating = null;

        return new CourseAnalyticsDTO(
            courseId: $course->id,
            courseTitle: $course->title,
            enrollmentCount: $enrollmentCount,
            averageCompletionPercentage: number_format($averageCompletion, 2, '.', ''),
            averageRating: $averageRating,
        );
    }

    /**
     * Export analytics data as a CSV file for the given date range.
     *
     * Validates: Requirement 9.4
     */
    public function exportCsv(DateRangeDTO $range): StreamedResponse
    {
        $metrics = $this->getDashboardMetrics($range);

        $courses = Course::orderBy('title')->get();
        $courseAnalytics = [];
        foreach ($courses as $course) {
            $courseAnalytics[] = $this->getCourseAnalytics($course->id);
        }

        $response = new StreamedResponse(function () use ($metrics, $courseAnalytics, $range): void {
            $handle = fopen('php://output', 'w');

            // Dashboard metrics section
            fputcsv($handle, ['GrowthPedia Analytics Report']);
            fputcsv($handle, ['Date Range', $range->startDate->toDateString(), $range->endDate->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, ['Dashboard Metrics']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Learner Count', $metrics->totalLearnerCount]);
            fputcsv($handle, ['Active Subscription Count', $metrics->activeSubscriptionCount]);
            fputcsv($handle, ['Total Course Count', $metrics->totalCourseCount]);
            fputcsv($handle, ['Total Revenue', $metrics->totalRevenue]);
            fputcsv($handle, []);

            // Course analytics section
            fputcsv($handle, ['Course Analytics']);
            fputcsv($handle, ['Course ID', 'Course Title', 'Enrollment Count', 'Avg Completion %', 'Avg Rating']);
            foreach ($courseAnalytics as $ca) {
                fputcsv($handle, [
                    $ca->courseId,
                    $ca->courseTitle,
                    $ca->enrollmentCount,
                    $ca->averageCompletionPercentage,
                    $ca->averageRating ?? 'N/A',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="analytics-report.csv"');

        return $response;
    }

    /**
     * Get a paginated list of flagged comments with flag reason, author, and lesson.
     *
     * Validates: Requirement 9.5
     */
    public function getFlaggedComments(int $page): PaginatedFlaggedCommentsDTO
    {
        $paginator = Comment::with(['user', 'lesson'])
            ->where('is_flagged', true)
            ->orderByDesc('updated_at')
            ->paginate(self::FLAGGED_COMMENTS_PER_PAGE, ['*'], 'page', $page);

        /** @var Comment[] $comments */
        $comments = $paginator->items();

        $flaggedComments = array_map(
            fn (Comment $comment) => new FlaggedCommentDTO(
                id: $comment->id,
                content: $comment->content,
                flagReason: $comment->flag_reason ?? '',
                authorName: $comment->user?->name ?? 'Unknown',
                lessonTitle: $comment->lesson?->title ?? 'Unknown',
                flaggedAt: $comment->updated_at,
            ),
            $comments,
        );

        return new PaginatedFlaggedCommentsDTO(
            comments: $flaggedComments,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage(),
        );
    }
}
