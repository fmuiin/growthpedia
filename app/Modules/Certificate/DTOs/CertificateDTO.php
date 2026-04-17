<?php

declare(strict_types=1);

namespace App\Modules\Certificate\DTOs;

use App\Shared\DTOs\BaseDTO;
use DateTimeInterface;

readonly class CertificateDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public int $enrollmentId,
        public int $userId,
        public int $courseId,
        public string $verificationCode,
        public string $learnerName,
        public string $courseTitle,
        public DateTimeInterface $completedAt,
        public ?string $pdfPath,
    ) {}
}
