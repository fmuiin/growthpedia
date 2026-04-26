import { Link, usePage, useForm } from '@inertiajs/react';
import type { PageProps } from '@/Types/user';
import type { ReactNode, FormEvent } from 'react';

interface AppLayoutProps {
    children: ReactNode;
}

export default function AppLayout({ children }: AppLayoutProps) {
    const { auth, subscription, flash } = usePage<PageProps>().props;
    const resendForm = useForm({});

    const showVerificationBanner = auth.user && !auth.user.emailVerifiedAt;

    function handleResend(e: FormEvent) {
        e.preventDefault();
        resendForm.post('/email/resend-verification', { preserveScroll: true });
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="border-b border-gray-200 bg-white shadow-sm">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-8">
                            <Link href="/" className="text-xl font-bold text-indigo-600">
                                GrowthPedia
                            </Link>
                            <div className="hidden items-center gap-6 sm:flex">
                                <Link
                                    href="/catalog"
                                    className="text-sm font-medium text-gray-600 hover:text-indigo-600"
                                >
                                    Catalog
                                </Link>
                                {auth.user?.role === 'learner' && (
                                    <>
                                        <Link
                                            href="/dashboard"
                                            className="text-sm font-medium text-gray-600 hover:text-indigo-600"
                                        >
                                            My Learning
                                        </Link>
                                        <Link
                                            href="/certificates"
                                            className="text-sm font-medium text-gray-600 hover:text-indigo-600"
                                        >
                                            Certificates
                                        </Link>
                                    </>
                                )}
                                {(auth.user?.role === 'instructor' || auth.user?.role === 'admin') && (
                                    <Link
                                        href="/courses"
                                        className="text-sm font-medium text-gray-600 hover:text-indigo-600"
                                    >
                                        My Courses
                                    </Link>
                                )}
                                {auth.user?.role === 'admin' && (
                                    <Link
                                        href="/admin/analytics"
                                        className="text-sm font-medium text-gray-600 hover:text-indigo-600"
                                    >
                                        Admin Panel
                                    </Link>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <div className="flex items-center gap-3">
                                    {subscription?.isActive ? (
                                        <Link
                                            href="/subscription"
                                            className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700"
                                        >
                                            Active Plan
                                        </Link>
                                    ) : auth.user.role === 'learner' ? (
                                        <Link
                                            href="/subscription/plans"
                                            className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700"
                                        >
                                            Subscribe
                                        </Link>
                                    ) : null}
                                    <span className="text-sm text-gray-700">{auth.user.name}</span>
                                    <span className="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 capitalize">
                                        {auth.user.role}
                                    </span>
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="text-sm font-medium text-gray-500 hover:text-gray-700"
                                    >
                                        Log out
                                    </Link>
                                </div>
                            ) : (
                                <Link href="/login" className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    Log in
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            {showVerificationBanner && (
                <div className="border-b border-amber-200 bg-amber-50 px-4 py-3" role="alert">
                    <div className="mx-auto flex max-w-7xl items-center justify-between sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2 text-sm text-amber-800">
                            <svg className="h-5 w-5 flex-shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fillRule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                            </svg>
                            <span>
                                Your email address is not verified. Please check your inbox for the verification link.
                            </span>
                        </div>
                        <form onSubmit={handleResend}>
                            <button
                                type="submit"
                                disabled={resendForm.processing}
                                className="ml-4 whitespace-nowrap rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-500 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {resendForm.processing ? 'Sending…' : 'Resend email'}
                            </button>
                        </form>
                    </div>
                </div>
            )}

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash.success && (
                    <div
                        className="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                        role="alert"
                    >
                        {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div
                        className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        role="alert"
                    >
                        {flash.error}
                    </div>
                )}

                {children}
            </main>
        </div>
    );
}
