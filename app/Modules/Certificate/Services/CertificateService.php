<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Services;

use App\Models\User;
use App\Modules\Certificate\Contracts\CertificateServiceInterface;
use App\Modules\Certificate\DTOs\CertificateDTO;
use App\Modules\Certificate\Models\Certificate;
use App\Modules\Course\Models\Course;
use App\Modules\Progress\Models\Enrollment;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateService implements CertificateServiceInterface
{
    public function generateCertificate(int $userId, int $courseId): CertificateDTO
    {
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($enrollment === null) {
            throw new EntityNotFoundException('Enrollment not found.');
        }

        // Return existing certificate if one already exists for this enrollment
        $existing = Certificate::where('enrollment_id', $enrollment->id)->first();

        if ($existing !== null) {
            return $this->toCertificateDTO($existing);
        }

        $user = User::find($userId);
        $course = Course::find($courseId);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $userId,
            'course_id' => $courseId,
            'verification_code' => Str::random(32),
            'learner_name' => $user->name,
            'course_title' => $course->title,
            'completed_at' => $enrollment->completed_at,
        ]);

        return $this->toCertificateDTO($certificate);
    }

    public function verifyCertificate(string $verificationCode): ?CertificateDTO
    {
        $certificate = Certificate::where('verification_code', $verificationCode)->first();

        if ($certificate === null) {
            return null;
        }

        return $this->toCertificateDTO($certificate);
    }

    public function downloadPdf(int $certificateId): StreamedResponse
    {
        $certificate = Certificate::find($certificateId);

        if ($certificate === null) {
            throw new EntityNotFoundException('Certificate not found.');
        }

        // Store the pdf_path on the certificate model
        if ($certificate->pdf_path === null) {
            $pdfPath = "certificates/certificate_{$certificate->id}.pdf";
            $certificate->update(['pdf_path' => $pdfPath]);
        }

        return new StreamedResponse(function () use ($certificate) {
            echo $this->generatePdfContent($certificate);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificate_' . $certificate->verification_code . '.pdf"',
        ]);
    }

    /**
     * @return Collection<int, CertificateDTO>
     */
    public function getUserCertificates(int $userId): Collection
    {
        $certificates = Certificate::where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->get();

        return $certificates->map(fn (Certificate $cert) => $this->toCertificateDTO($cert));
    }

    private function toCertificateDTO(Certificate $certificate): CertificateDTO
    {
        return new CertificateDTO(
            id: $certificate->id,
            enrollmentId: $certificate->enrollment_id,
            userId: $certificate->user_id,
            courseId: $certificate->course_id,
            verificationCode: $certificate->verification_code,
            learnerName: $certificate->learner_name,
            courseTitle: $certificate->course_title,
            completedAt: $certificate->completed_at,
            pdfPath: $certificate->pdf_path,
        );
    }

    private function generatePdfContent(Certificate $certificate): string
    {
        $completedDate = $certificate->completed_at->format('F j, Y');

        // Simple PDF structure (minimal valid PDF)
        $content = "Certificate of Completion\n\n";
        $content .= "This certifies that\n\n";
        $content .= "{$certificate->learner_name}\n\n";
        $content .= "has successfully completed the course\n\n";
        $content .= "{$certificate->course_title}\n\n";
        $content .= "on {$completedDate}\n\n";
        $content .= "Verification Code: {$certificate->verification_code}\n";

        return $content;
    }
}
