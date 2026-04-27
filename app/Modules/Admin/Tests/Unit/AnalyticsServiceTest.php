<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests\Unit;

use App\Models\User;
use App\Modules\Admin\DTOs\CourseAnalyticsDTO;
use App\Modules\Admin\DTOs\DashboardMetricsDTO;
use App\Modules\Admin\DTOs\PaginatedFlaggedCommentsDTO;
use App\Modules\Admin\Services\AnalyticsService;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Discussion\Models\Comment;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Progress\Models\Enrollment;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Modules\Subscription\Models\Subscription;
use App\Shared\DTOs\DateRangeDTO;
use App\Shared\Exceptions\EntityNotFoundException;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService();
    }

    // --- getDashboardMetrics ---

    public function test_get_dashboard_metrics_returns_correct_counts(): void
    {
        // Create learners
        User::factory()->create(['role' => 'learner']);
        User::factory()->create(['role' => 'learner']);
        User::factory()->create(['role' => 'admin']);

        // Create courses
        $admin = User::factory()->create(['role' => 'admin']);
        Course::create([
            'created_by' => $admin->id,
            'title' => 'Course 1',
            'description' => 'Desc',
            'category' => 'Tech',
            'status' => 'published',
        ]);
        Course::create([
            'created_by' => $admin->id,
            'title' => 'Course 2',
            'description' => 'Desc',
            'category' => 'Tech',
            'status' => 'draft',
        ]);

        // Create subscriptions
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $learner1 = User::where('role', 'learner')->first();
        $sub = Subscription::create([
            'user_id' => $learner1->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Create payment transactions
        PaymentTransaction::create([
            'subscription_id' => $sub->id,
            'gateway_transaction_id' => 'txn_001',
            'amount' => '150.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);
        PaymentTransaction::create([
            'subscription_id' => $sub->id,
            'gateway_transaction_id' => 'txn_002',
            'amount' => '50.00',
            'currency' => 'IDR',
            'status' => 'failed',
            'type' => 'charge',
        ]);

        $range = new DateRangeDTO(
            startDate: CarbonImmutable::now()->subDays(30)->startOfDay(),
            endDate: CarbonImmutable::now()->endOfDay(),
        );

        $result = $this->service->getDashboardMetrics($range);

        $this->assertInstanceOf(DashboardMetricsDTO::class, $result);
        $this->assertEquals(2, $result->totalLearnerCount);
        $this->assertEquals(1, $result->activeSubscriptionCount);
        $this->assertEquals(2, $result->totalCourseCount);
        $this->assertEquals('150.00', $result->totalRevenue);
    }

    public function test_get_dashboard_metrics_filters_revenue_by_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = MembershipPlan::create([
            'name' => 'Plan',
            'price' => '100.00',
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);
        $sub = Subscription::create([
            'user_id' => $admin->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Transaction inside range
        $txnIn = PaymentTransaction::create([
            'subscription_id' => $sub->id,
            'gateway_transaction_id' => 'txn_in',
            'amount' => '200.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);

        // Transaction outside range — manually update created_at to bypass Eloquent auto-management
        $txnOut = PaymentTransaction::create([
            'subscription_id' => $sub->id,
            'gateway_transaction_id' => 'txn_out',
            'amount' => '300.00',
            'currency' => 'IDR',
            'status' => 'success',
            'type' => 'charge',
        ]);
        PaymentTransaction::where('id', $txnOut->id)
            ->update(['created_at' => CarbonImmutable::now()->subDays(60)]);

        $range = new DateRangeDTO(
            startDate: CarbonImmutable::now()->subDays(30)->startOfDay(),
            endDate: CarbonImmutable::now()->endOfDay(),
        );

        $result = $this->service->getDashboardMetrics($range);

        $this->assertEquals('200.00', $result->totalRevenue);
    }

    public function test_get_dashboard_metrics_returns_zero_revenue_when_no_transactions(): void
    {
        $range = new DateRangeDTO(
            startDate: CarbonImmutable::now()->subDays(30)->startOfDay(),
            endDate: CarbonImmutable::now()->endOfDay(),
        );

        $result = $this->service->getDashboardMetrics($range);

        $this->assertEquals(0, $result->totalLearnerCount);
        $this->assertEquals(0, $result->activeSubscriptionCount);
        $this->assertEquals(0, $result->totalCourseCount);
        $this->assertEquals('0.00', $result->totalRevenue);
    }

    // --- getCourseAnalytics ---

    public function test_get_course_analytics_returns_correct_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Analytics Course',
            'description' => 'Desc',
            'category' => 'Tech',
            'status' => 'published',
        ]);

        $learner1 = User::factory()->create(['role' => 'learner']);
        $learner2 = User::factory()->create(['role' => 'learner']);

        Enrollment::create([
            'user_id' => $learner1->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'completion_percentage' => 75.00,
        ]);
        Enrollment::create([
            'user_id' => $learner2->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'completion_percentage' => 25.00,
        ]);

        $result = $this->service->getCourseAnalytics($course->id);

        $this->assertInstanceOf(CourseAnalyticsDTO::class, $result);
        $this->assertEquals($course->id, $result->courseId);
        $this->assertEquals('Analytics Course', $result->courseTitle);
        $this->assertEquals(2, $result->enrollmentCount);
        $this->assertEquals('50.00', $result->averageCompletionPercentage);
        $this->assertNull($result->averageRating);
    }

    public function test_get_course_analytics_with_no_enrollments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Empty Course',
            'description' => 'Desc',
            'category' => 'Tech',
            'status' => 'published',
        ]);

        $result = $this->service->getCourseAnalytics($course->id);

        $this->assertEquals(0, $result->enrollmentCount);
        $this->assertEquals('0.00', $result->averageCompletionPercentage);
    }

    public function test_get_course_analytics_throws_when_course_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->service->getCourseAnalytics(999);
    }

    // --- exportCsv ---

    public function test_export_csv_returns_streamed_response(): void
    {
        $range = new DateRangeDTO(
            startDate: CarbonImmutable::now()->subDays(30)->startOfDay(),
            endDate: CarbonImmutable::now()->endOfDay(),
        );

        $result = $this->service->exportCsv($range);

        $this->assertInstanceOf(StreamedResponse::class, $result);
        $this->assertEquals('text/csv', $result->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $result->headers->get('Content-Disposition'));
        $this->assertStringContainsString('analytics-report.csv', $result->headers->get('Content-Disposition'));
    }

    public function test_export_csv_contains_metrics_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'learner']);

        Course::create([
            'created_by' => $admin->id,
            'title' => 'CSV Course',
            'description' => 'Desc',
            'category' => 'Tech',
            'status' => 'published',
        ]);

        $range = new DateRangeDTO(
            startDate: CarbonImmutable::now()->subDays(30)->startOfDay(),
            endDate: CarbonImmutable::now()->endOfDay(),
        );

        $response = $this->service->exportCsv($range);

        // Capture the streamed output
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('GrowthPedia Analytics Report', $content);
        $this->assertStringContainsString('Dashboard Metrics', $content);
        $this->assertStringContainsString('Total Learner Count', $content);
        $this->assertStringContainsString('Course Analytics', $content);
        $this->assertStringContainsString('CSV Course', $content);
    }

    // --- getFlaggedComments ---

    public function test_get_flagged_comments_returns_paginated_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Course',
            'description' => 'Desc',
            'category' => 'Tech',
            'status' => 'published',
        ]);
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $author = User::factory()->create(['role' => 'learner']);
        $flagger = User::factory()->create(['role' => 'admin']);

        // Flagged comment
        Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $author->id,
            'content' => 'Inappropriate content',
            'is_flagged' => true,
            'flag_reason' => 'Spam',
            'flagged_by' => $flagger->id,
        ]);

        // Non-flagged comment
        Comment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $author->id,
            'content' => 'Normal comment',
            'is_flagged' => false,
        ]);

        $result = $this->service->getFlaggedComments(1);

        $this->assertInstanceOf(PaginatedFlaggedCommentsDTO::class, $result);
        $this->assertCount(1, $result->comments);
        $this->assertEquals(1, $result->total);
        $this->assertEquals('Inappropriate content', $result->comments[0]->content);
        $this->assertEquals('Spam', $result->comments[0]->flagReason);
        $this->assertEquals($author->name, $result->comments[0]->authorName);
        $this->assertEquals('Lesson 1', $result->comments[0]->lessonTitle);
    }

    public function test_get_flagged_comments_returns_empty_when_none_flagged(): void
    {
        $result = $this->service->getFlaggedComments(1);

        $this->assertCount(0, $result->comments);
        $this->assertEquals(0, $result->total);
    }
}
