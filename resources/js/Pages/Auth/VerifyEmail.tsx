import { Link } from '@inertiajs/react';
import GuestLayout from '@/Components/Layout/GuestLayout';

export default function VerifyEmail() {
    return (
        <GuestLayout>
            <div className="text-center">
                <h2 className="mb-2 text-2xl font-semibold text-gray-900">
                    Email verification
                </h2>
                <p className="mb-6 text-sm text-gray-600">
                    Your email is being verified. If you were not redirected automatically,
                    please check your email for the verification link or try logging in.
                </p>
                <Link
                    href="/login"
                    className="inline-block rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                    Go to login
                </Link>
            </div>
        </GuestLayout>
    );
}
