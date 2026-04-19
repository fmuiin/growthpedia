import { Link, usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types/user';
import type { ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
}

export default function AppLayout({ children }: AppLayoutProps) {
    const { auth, flash } = usePage<PageProps>().props;

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
                                {auth.user?.role === 'instructor' || auth.user?.role === 'admin' ? (
                                    <Link
                                        href="/courses"
                                        className="text-sm font-medium text-gray-600 hover:text-indigo-600"
                                    >
                                        My Courses
                                    </Link>
                                ) : null}
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <div className="flex items-center gap-3">
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
