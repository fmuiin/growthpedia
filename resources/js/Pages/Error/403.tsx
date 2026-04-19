import { Head, Link } from '@inertiajs/react';

interface Props {
    status?: number;
    message?: string;
}

export default function Forbidden({ status = 403, message }: Props) {
    return (
        <>
            <Head title="Access Denied" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
                <div className="text-center">
                    <p className="text-6xl font-bold text-indigo-600">{status}</p>
                    <h1 className="mt-4 text-3xl font-bold tracking-tight text-gray-900">
                        Access Denied
                    </h1>
                    <p className="mt-4 text-base text-gray-600">
                        {message || 'You don\u2019t have permission to access this page. An active subscription may be required.'}
                    </p>
                    <div className="mt-8 flex items-center justify-center gap-4">
                        <Link
                            href="/subscription/plans"
                            className="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            View Plans
                        </Link>
                        <Link
                            href="/"
                            className="text-sm font-semibold text-gray-600 hover:text-gray-900"
                        >
                            Go Home &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
