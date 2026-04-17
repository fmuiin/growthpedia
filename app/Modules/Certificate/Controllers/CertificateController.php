<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificate\Contracts\CertificateServiceInterface;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateServiceInterface $certificateService,
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        $certificates = $this->certificateService->getUserCertificates($user->id);

        return Inertia::render('Certificate/MyCertificates', [
            'certificates' => $certificates->map(fn ($cert) => $cert->toArray())->values()->all(),
        ]);
    }

    public function download(int $certificateId): StreamedResponse|RedirectResponse
    {
        try {
            return $this->certificateService->downloadPdf($certificateId);
        } catch (EntityNotFoundException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function verify(Request $request): Response
    {
        $verificationCode = $request->query('verification_code');
        $result = null;
        $searched = false;

        if ($verificationCode !== null && $verificationCode !== '') {
            $searched = true;
            $cert = $this->certificateService->verifyCertificate((string) $verificationCode);
            $result = $cert?->toArray();
        }

        return Inertia::render('Certificate/VerifyCertificate', [
            'result' => $result,
            'searched' => $searched,
        ]);
    }

    public function verifySubmit(Request $request): Response
    {
        $request->validate([
            'verification_code' => ['required', 'string'],
        ]);

        $verificationCode = $request->input('verification_code');
        $cert = $this->certificateService->verifyCertificate($verificationCode);

        return Inertia::render('Certificate/VerifyCertificate', [
            'result' => $cert?->toArray(),
            'searched' => true,
        ]);
    }
}
