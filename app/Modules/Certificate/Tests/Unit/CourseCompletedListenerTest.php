<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Tests\Unit;

use App\Modules\Certificate\Contracts\CertificateServiceInterface;
use App\Modules\Certificate\DTOs\CertificateDTO;
use App\Modules\Certificate\Listeners\CourseCompletedListener;
use App\Modules\Progress\Events\CourseCompleted;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class CourseCompletedListenerTest extends TestCase
{
    public function test_handle_calls_generate_certificate_with_correct_arguments(): void
    {
        $userId = 42;
        $courseId = 7;
        $enrollmentId = 100;

        $certificateService = Mockery::mock(CertificateServiceInterface::class);
        $certificateService
            ->shouldReceive('generateCertificate')
            ->once()
            ->with($userId, $courseId)
            ->andReturn(new CertificateDTO(
                id: 1,
                enrollmentId: $enrollmentId,
                userId: $userId,
                courseId: $courseId,
                verificationCode: 'abc123',
                learnerName: 'Test User',
                courseTitle: 'Test Course',
                completedAt: Carbon::now(),
                pdfPath: null,
            ));

        $listener = new CourseCompletedListener($certificateService);
        $event = new CourseCompleted($userId, $courseId, $enrollmentId);

        $listener->handle($event);

        // Mockery assertions are verified automatically on tearDown
    }
}
