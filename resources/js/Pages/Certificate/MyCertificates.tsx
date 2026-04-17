import AppLayout from '@/Components/Layout/AppLayout';
import type { CertificateType } from '@/Types/certificate';

interface MyCertificatesProps {
    certificates: CertificateType[];
}

export default function MyCertificates({ certificates }: MyCertificatesProps) {
    return (
        <AppLayout>
            <div className="mx-auto max-w-4xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">My Certificates</h1>

                {certificates.length === 0 ? (
                    <div className="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200">
                        <p className="text-sm text-gray-500">
                            You haven't earned any certificates yet. Complete a course to receive one.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {certificates.map((certificate) => (
                            <div
                                key={certificate.id}
                                className="flex items-center justify-between rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
                            >
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        {certificate.courseTitle}
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Completed on{' '}
                                        {new Date(certificate.completedAt).toLocaleDateString()}
                                    </p>
                                    <p className="mt-1 text-xs text-gray-400">
                                        Verification: {certificate.verificationCode}
                                    </p>
                                </div>
                                <a
                                    href={`/certificates/${certificate.id}/download`}
                                    className="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Download
                                </a>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
