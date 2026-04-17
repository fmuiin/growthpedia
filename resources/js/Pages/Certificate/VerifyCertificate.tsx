import { useForm } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import type { CertificateType } from '@/Types/certificate';
import type { FormEvent } from 'react';

interface VerifyCertificateProps {
    result?: CertificateType | null;
    searched?: boolean;
}

export default function VerifyCertificate({ result, searched }: VerifyCertificateProps) {
    const form = useForm({
        verification_code: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        form.post('/verify');
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-lg">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Verify Certificate</h1>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <form onSubmit={handleSubmit}>
                        <label
                            htmlFor="verification_code"
                            className="block text-sm font-medium text-gray-700"
                        >
                            Verification Code
                        </label>
                        <input
                            id="verification_code"
                            type="text"
                            value={form.data.verification_code}
                            onChange={(e) =>
                                form.setData('verification_code', e.target.value)
                            }
                            className="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            placeholder="Enter verification code"
                        />
                        {form.errors.verification_code && (
                            <p className="mt-1 text-sm text-red-600">
                                {form.errors.verification_code}
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="mt-4 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {form.processing ? 'Verifying...' : 'Verify'}
                        </button>
                    </form>
                </div>

                {searched && result && (
                    <div className="mt-6 rounded-xl border border-green-200 bg-green-50 p-6">
                        <h2 className="text-lg font-semibold text-green-800">
                            Certificate Verified
                        </h2>
                        <dl className="mt-4 space-y-2 text-sm">
                            <div className="flex justify-between">
                                <dt className="font-medium text-gray-600">Learner</dt>
                                <dd className="text-gray-900">{result.learnerName}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="font-medium text-gray-600">Course</dt>
                                <dd className="text-gray-900">{result.courseTitle}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="font-medium text-gray-600">Completed</dt>
                                <dd className="text-gray-900">
                                    {new Date(result.completedAt).toLocaleDateString()}
                                </dd>
                            </div>
                        </dl>
                    </div>
                )}

                {searched && !result && (
                    <div className="mt-6 rounded-xl border border-red-200 bg-red-50 p-6 text-center">
                        <p className="text-sm text-red-700">
                            No certificate found with that verification code.
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
