import { Link, usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types/user';
import type { ReactNode } from 'react';

interface LandingLayoutProps {
    children: ReactNode;
}

export default function LandingLayout({ children }: LandingLayoutProps) {
    const { auth, branding } = usePage<PageProps>().props;
    const siteName = branding?.siteName ?? 'GrowthPedia';
    const siteInitial = siteName.charAt(0).toUpperCase();

    return (
        <div className="min-h-screen bg-white text-gray-900">
            <header className="sticky top-0 z-50 border-b border-gray-100 bg-white/90 backdrop-blur-md">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-2">
                        {branding?.logoUrl ? (
                            <img src={branding.logoUrl} alt={siteName} className="h-9 w-auto" />
                        ) : (
                            <span
                                className="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold text-white shadow-sm"
                                style={{
                                    background: `linear-gradient(to bottom right, ${branding?.primaryColor ?? '#7C3AED'}, ${branding?.secondaryColor ?? '#4F46E5'})`,
                                }}
                            >
                                {siteInitial}
                            </span>
                        )}
                        <span className="text-lg font-semibold tracking-tight text-gray-900">{siteName}</span>
                    </Link>
                    <nav className="hidden items-center gap-8 md:flex" aria-label="Primary">
                        <Link href="/catalog" className="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                            Courses
                        </Link>
                        <a href="#pricing" className="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                            Pricing
                        </a>
                        <a href="#features" className="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                            About
                        </a>
                        <a href="#testimonials" className="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                            Stories
                        </a>
                    </nav>
                    <div className="flex items-center gap-3">
                        {auth.user ? (
                            <>
                                <Link
                                    href="/dashboard"
                                    className="hidden text-sm font-medium text-gray-600 hover:text-gray-900 sm:inline"
                                >
                                    Dashboard
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                                >
                                    Log out
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link href="/login" className="text-sm font-medium text-gray-600 hover:text-gray-900">
                                    Login
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                                >
                                    Get started
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            {children}

            <footer className="border-t border-gray-900 bg-gray-950 text-gray-300">
                <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-6 lg:gap-8">
                        <div className="lg:col-span-2">
                            <Link href="/" className="flex items-center gap-2">
                                {branding?.logoUrl ? (
                                    <img src={branding.logoUrl} alt={siteName} className="h-9 w-auto" />
                                ) : (
                                    <span
                                        className="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold text-white"
                                        style={{
                                            background: `linear-gradient(to bottom right, ${branding?.primaryColor ?? '#7C3AED'}, ${branding?.secondaryColor ?? '#4F46E5'})`,
                                        }}
                                    >
                                        {siteInitial}
                                    </span>
                                )}
                                <span className="text-lg font-semibold text-white">{siteName}</span>
                            </Link>
                            <p className="mt-4 max-w-sm text-sm leading-relaxed text-gray-400">
                                The modern way to deliver courses, track progress, and certify completions — built for
                                teams that care about learning outcomes.
                            </p>
                            <div className="mt-6 flex gap-3">
                                {['X', 'in', '▶', '◉'].map((label) => (
                                    <span
                                        key={label}
                                        className="flex h-9 w-9 cursor-default items-center justify-center rounded-full border border-gray-700 bg-gray-900 text-xs font-medium text-gray-400"
                                        aria-hidden
                                    >
                                        {label}
                                    </span>
                                ))}
                            </div>
                        </div>
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-gray-500">Company</h3>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li>
                                    <a href="#features" className="hover:text-white">
                                        About
                                    </a>
                                </li>
                                <li>
                                    <a href="#features" className="hover:text-white">
                                        Careers
                                    </a>
                                </li>
                                <li>
                                    <a href="#testimonials" className="hover:text-white">
                                        Stories
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-gray-500">Resources</h3>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li>
                                    <Link href="/catalog" className="hover:text-white">
                                        Course catalog
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/plans" className="hover:text-white">
                                        Membership
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/login" className="hover:text-white">
                                        Help center
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-gray-500">Legal</h3>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li>
                                    <span className="cursor-default text-gray-500">Terms &amp; Conditions</span>
                                </li>
                                <li>
                                    <span className="cursor-default text-gray-500">Privacy Policy</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-gray-500">Social</h3>
                            <ul className="mt-4 space-y-3 text-sm">
                                <li>
                                    <span className="cursor-default text-gray-500">Twitter / X</span>
                                </li>
                                <li>
                                    <span className="cursor-default text-gray-500">LinkedIn</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div className="mt-12 border-t border-gray-800 pt-8 text-xs text-gray-500">
                        <p>&copy; {new Date().getFullYear()} {siteName}. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
