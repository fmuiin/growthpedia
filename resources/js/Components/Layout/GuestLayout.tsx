import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types/user';
import type { ReactNode } from 'react';

interface GuestLayoutProps {
    children: ReactNode;
}

export default function GuestLayout({ children }: GuestLayoutProps) {
    const { flash, branding } = usePage<PageProps>().props;
    const siteName = branding?.siteName ?? 'GrowthPedia';

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12">
            <div className="w-full max-w-md">
                <div className="mb-8 text-center">
                    {branding?.logoUrl ? (
                        <div className="flex justify-center">
                            <img src={branding.logoUrl} alt={siteName} className="h-10 w-auto" />
                        </div>
                    ) : null}
                    <h1
                        className="text-3xl font-bold"
                        style={{ color: branding?.primaryColor ?? '#4F46E5' }}
                    >
                        {siteName}
                    </h1>
                </div>

                {flash?.success && (
                    <div
                        className="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                        role="alert"
                    >
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div
                        className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        role="alert"
                    >
                        {flash.error}
                    </div>
                )}

                <div className="rounded-xl bg-white p-8 shadow-md">
                    {children}
                </div>
            </div>
        </div>
    );
}
