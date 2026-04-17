<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Listeners;

use App\Modules\Certificate\Contracts\CertificateServiceInterface;
use App\Modules\Progress\Events\CourseCompleted;

class CourseCompletedListener
{
    public function __construct(
        private readonly CertificateServiceInterface $certificateService,
    ) {}

    public function handle(CourseCompleted $event): void
    {
        $this->certificateService->generateCertificate($event->userId, $event->courseId);
    }
}
