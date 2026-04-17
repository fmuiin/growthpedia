<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Contracts;

use App\Modules\Certificate\DTOs\CertificateDTO;
use App\Shared\Contracts\ServiceInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface CertificateServiceInterface extends ServiceInterface
{
    public function generateCertificate(int $userId, int $courseId): CertificateDTO;

    public function verifyCertificate(string $verificationCode): ?CertificateDTO;

    public function downloadPdf(int $certificateId): StreamedResponse;

    /**
     * @return Collection<int, CertificateDTO>
     */
    public function getUserCertificates(int $userId): Collection;
}
