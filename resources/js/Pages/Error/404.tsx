import { Head, Link } from '@inertiajs/react';

interface Props {
    status?: number;
    message?: string;
}

export default function NotFound({ status = 404, message }: Props) {
    return (
        <>
            <Head title="Page Not Found" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
                <div className="text-center">
                    <p className="text-6xl font-bold text-indigo-600">{status}</p>
                    <h1 className="mt-4 text-3xl font-bold tracking-tight text-gray-900">
                        Page Not Found
                    </h1>
                    <p className="mt-4 text-base text-gray-600">
                        {message || 'Sorry, we couldn\u2019t find the page you\u2019re looking for.'}
                    </p>
                    <div className="mt-8">
                        <Link
                            href="/"
                            className="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Go Home
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
