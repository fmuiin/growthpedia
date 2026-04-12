import { useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/Layout/GuestLayout';
import type { FormEvent } from 'react';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/reset-password');
    }

    return (
        <GuestLayout>
            <h2 className="mb-6 text-center text-2xl font-semibold text-gray-900">
                Set a new password
            </h2>

            <form onSubmit={handleSubmit} className="space-y-5">
                <input type="hidden" name="token" value={data.token} />
                <input type="hidden" name="email" value={data.email} />

                <div>
                    <label htmlFor="password" className="mb-1 block text-sm font-medium text-gray-700">
                        New password
                    </label>
                    <input
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        required
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        aria-describedby={errors.password ? 'password-error' : undefined}
                    />
                    {errors.password && (
                        <p id="password-error" className="mt-1 text-sm text-red-600" role="alert">
                            {errors.password}
                        </p>
                    )}
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="mb-1 block text-sm font-medium text-gray-700">
                        Confirm new password
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        required
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        aria-describedby={errors.password_confirmation ? 'password-confirmation-error' : undefined}
                    />
                    {errors.password_confirmation && (
                        <p id="password-confirmation-error" className="mt-1 text-sm text-red-600" role="alert">
                            {errors.password_confirmation}
                        </p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {processing ? 'Resetting…' : 'Reset password'}
                </button>
            </form>
        </GuestLayout>
    );
}
