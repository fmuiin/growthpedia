<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Tests\Unit;

use App\Models\User;
use App\Modules\Certificate\DTOs\CertificateDTO;
use App\Modules\Certificate\Models\Certificate;
use App\Modules\Certificate\Services\CertificateService;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Modules\Progress\Models\Enrollment;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use RefreshDatabase;

    private CertificateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CertificateService();
    }

    private function createCompletedEnrollment(): array
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::create([
            'created_by' => $admin->id,
            'title' => 'Laravel Mastery',
            'description' => 'Master Laravel',
            'category' => 'Programming',
            'status' => 'published',
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'text',
            'content_body' => 'Content',
            'sort_order' => 1,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => Carbon::parse('2024-01-01'),
            'completion_percentage' => 100.00,
            'completed_at' => Carbon::parse('2024-06-15 10:00:00'),
        ]);

        return [$user, $course, $enrollment];
    }

    // --- generateCertificate() ---

    public function test_generate_certificate_creates_certificate_with_correct_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-06-15 10:00:00'));

        [$user, $course, $enrollment] = $this->createCompletedEnrollment();

        $result = $this->service->generateCertificate($user->id, $course->id);

        $this->assertInstanceOf(CertificateDTO::class, $result);
        $this->assertEquals($enrollment->id, $result->enrollmentId);
        $this->assertEquals($user->id, $result->userId);
        $this->assertEquals($course->id, $result->courseId);
        $this->assertEquals('Jane Doe', $result->learnerName);
        $this->assertEquals('Laravel Mastery', $result->courseTitle);
        $this->assertEquals('2024-06-15 10:00:00', $result->completedAt->format('Y-m-d H:i:s'));
        $this->assertNotEmpty($result->verificationCode);
        $this->assertEquals(32, strlen($result->verificationCode));

        // Verify persisted in database
        $this->assertDatabaseHas('certificates', [
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'course_id' => $course->id,
            'learner_name' => 'Jane Doe',
            'course_title' => 'Laravel Mastery',
        ]);
    }

    public function test_generate_certificate_is_idempotent(): void
    {
        [$user, $course, $enrollment] = $this->createCompletedEnrollment();

        $first = $this->service->generateCertificate($user->id, $course->id);
        $second = $this->service->generateCertificate($user->id, $course->id);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals($first->verificationCode, $second->verificationCode);
        $this->assertEquals(1, Certificate::where('enrollment_id', $enrollment->id)->count());
    }

    public function test_generate_certificate_throws_when_enrollment_not_found(): void
    {
        $user = User::factory()->create();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Enrollment not found.');
        $this->service->generateCertificate($user->id, 999);
    }

    // --- verifyCertificate() ---

    public function test_verify_certificate_returns_correct_data_for_valid_code(): void
    {
        [$user, $course, $enrollment] = $this->createCompletedEnrollment();

        $generated = $this->service->generateCertificate($user->id, $course->id);

        $result = $this->service->verifyCertificate($generated->verificationCode);

        $this->assertNotNull($result);
        $this->assertInstanceOf(CertificateDTO::class, $result);
        $this->assertEquals('Jane Doe', $result->learnerName);
        $this->assertEquals('Laravel Mastery', $result->courseTitle);
        $this->assertEquals($generated->completedAt->format('Y-m-d H:i:s'), $result->completedAt->format('Y-m-d H:i:s'));
        $this->assertEquals($generated->verificationCode, $result->verificationCode);
    }

    public function test_verify_certificate_returns_null_for_invalid_code(): void
    {
        $result = $this->service->verifyCertificate('nonexistent_code_12345');

        $this->assertNull($result);
    }

    // --- downloadPdf() ---

    public function test_download_pdf_returns_streamed_response(): void
    {
        [$user, $course, $enrollment] = $this->createCompletedEnrollment();

        $generated = $this->service->generateCertificate($user->id, $course->id);

        $response = $this->service->downloadPdf($generated->id);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        // Verify pdf_path was stored
        $certificate = Certificate::find($generated->id);
        $this->assertNotNull($certificate->pdf_path);
    }

    public function test_download_pdf_throws_when_certificate_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Certificate not found.');
        $this->service->downloadPdf(999);
    }

    // --- getUserCertificates() ---

    public function test_get_user_certificates_returns_all_user_certificates(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Create two courses with completed enrollments
        $course1 = Course::create([
            'created_by' => $admin->id,
            'title' => 'Course A',
            'description' => 'Desc A',
            'category' => 'Cat',
            'status' => 'published',
        ]);
        $course2 = Course::create([
            'created_by' => $admin->id,
            'title' => 'Course B',
            'description' => 'Desc B',
            'category' => 'Cat',
            'status' => 'published',
        ]);

        $module1 = CourseModule::create(['course_id' => $course1->id, 'title' => 'M1', 'sort_order' => 1]);
        $module2 = CourseModule::create(['course_id' => $course2->id, 'title' => 'M2', 'sort_order' => 1]);
        Lesson::create(['course_module_id' => $module1->id, 'title' => 'L1', 'content_type' => 'text', 'sort_order' => 1]);
        Lesson::create(['course_module_id' => $module2->id, 'title' => 'L2', 'content_type' => 'text', 'sort_order' => 1]);

        $enrollment1 = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
            'enrolled_at' => Carbon::parse('2024-01-01'),
            'completion_percentage' => 100.00,
            'completed_at' => Carbon::parse('2024-03-01'),
        ]);
        $enrollment2 = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course2->id,
            'enrolled_at' => Carbon::parse('2024-02-01'),
            'completion_percentage' => 100.00,
            'completed_at' => Carbon::parse('2024-06-01'),
        ]);

        $this->service->generateCertificate($user->id, $course1->id);
        $this->service->generateCertificate($user->id, $course2->id);

        $certificates = $this->service->getUserCertificates($user->id);

        $this->assertCount(2, $certificates);

        // Should be ordered by completed_at descending (Course B first)
        $this->assertEquals('Course B', $certificates->first()->courseTitle);
        $this->assertEquals('Course A', $certificates->last()->courseTitle);
    }

    public function test_get_user_certificates_returns_empty_collection_for_no_certificates(): void
    {
        $user = User::factory()->create();

        $certificates = $this->service->getUserCertificates($user->id);

        $this->assertCount(0, $certificates);
    }
}
