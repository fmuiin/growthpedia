import { useForm, Link } from '@inertiajs/react';
import GuestLayout from '@/Components/Layout/GuestLayout';
import type { FormEvent } from 'react';

export default function ForgotPassword() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/forgot-password');
    }

    return (
        <GuestLayout>
            <h2 className="mb-2 text-center text-2xl font-semibold text-gray-900">
                Reset your password
            </h2>
            <p className="mb-6 text-center text-sm text-gray-600">
                Enter your email and we&apos;ll send you a reset link.
            </p>

            <form onSubmit={handleSubmit} className="space-y-5">
                <div>
                    <label htmlFor="email" className="mb-1 block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        autoComplete="email"
                        required
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        aria-describedby={errors.email ? 'email-error' : undefined}
                    />
                    {errors.email && (
                        <p id="email-error" className="mt-1 text-sm text-red-600" role="alert">
                            {errors.email}
                        </p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {processing ? 'Sending…' : 'Send reset link'}
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600">
                <Link href="/login" className="font-medium text-indigo-600 hover:text-indigo-500">
                    Back to login
                </Link>
            </p>
        </GuestLayout>
    );
}
