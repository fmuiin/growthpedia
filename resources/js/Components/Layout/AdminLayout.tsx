import { Link, usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types/user';
import type { ReactNode } from 'react';

interface AdminLayoutProps {
    children: ReactNode;
}

const navItems = [
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/analytics', label: 'Analytics' },
    { href: '/admin/analytics/flagged-comments', label: 'Flagged Comments' },
    { href: '/admin/plans', label: 'Membership Plans' },
];

export default function AdminLayout({ children }: AdminLayoutProps) {
    const { auth, flash } = usePage<PageProps>().props;
    const currentUrl = usePage().url;

    function isActive(href: string): boolean {
        return currentUrl.startsWith(href);
    }

    return (
        <div className="flex min-h-screen bg-gray-50">
            {/* Sidebar */}
            <aside className="flex w-64 flex-col border-r border-gray-200 bg-white">
                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                    <Link href="/" className="text-xl font-bold text-indigo-600">
                        GrowthPedia
                    </Link>
                </div>

                <nav className="flex-1 px-4 py-6" aria-label="Admin navigation">
                    <p className="mb-3 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                        Admin Panel
                    </p>
                    <ul className="space-y-1">
                        {navItems.map((item) => (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${
                                        isActive(item.href)
                                            ? 'bg-indigo-50 text-indigo-700'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                    aria-current={isActive(item.href) ? 'page' : undefined}
                                >
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div className="border-t border-gray-200 px-4 py-4">
                    <div className="flex items-center gap-3 px-2">
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-gray-900">
                                {auth.user?.name}
                            </p>
                            <p className="truncate text-xs text-gray-500">
                                {auth.user?.role}
                            </p>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-sm font-medium text-gray-500 hover:text-gray-700"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex flex-1 flex-col">
                <header className="flex h-16 items-center border-b border-gray-200 bg-white px-8">
                    <Link href="/" className="text-sm font-medium text-gray-500 hover:text-gray-700">
                        ← Back to site
                    </Link>
                </header>

                <main className="flex-1 px-8 py-8">
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
        </div>
    );
}
