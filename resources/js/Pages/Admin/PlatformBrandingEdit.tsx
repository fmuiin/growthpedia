import AdminLayout from '@/Components/Layout/AdminLayout';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { PlatformBrandingType } from '@/Types/branding';

interface PlatformBrandingEditProps {
    branding: PlatformBrandingType;
}

export default function PlatformBrandingEdit({ branding }: PlatformBrandingEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        site_name: branding.siteName ?? '',
        tagline: branding.tagline ?? '',
        logo_url: branding.logoUrl ?? '',
        favicon_url: branding.faviconUrl ?? '',
        primary_color: branding.primaryColor ?? '#3B82F6',
        secondary_color: branding.secondaryColor ?? '#1E40AF',
        footer_text: branding.footerText ?? '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        put('/admin/branding/platform');
    }

    return (
        <AdminLayout>
            <div className="mx-auto max-w-2xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Platform Branding</h1>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Site Name */}
                        <div>
                            <label htmlFor="site_name" className="mb-1 block text-sm font-medium text-gray-700">
                                Site Name
                            </label>
                            <input
                                id="site_name"
                                type="text"
                                required
                                maxLength={255}
                                value={data.site_name}
                                onChange={(e) => setData('site_name', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.site_name ? 'site-name-error' : undefined}
                            />
                            {errors.site_name && (
                                <p id="site-name-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.site_name}
                                </p>
                            )}
                        </div>

                        {/* Tagline */}
                        <div>
                            <label htmlFor="tagline" className="mb-1 block text-sm font-medium text-gray-700">
                                Tagline
                            </label>
                            <input
                                id="tagline"
                                type="text"
                                maxLength={500}
                                value={data.tagline}
                                onChange={(e) => setData('tagline', e.target.value)}
                                placeholder="A short description of your platform"
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.tagline ? 'tagline-error' : undefined}
                            />
                            {errors.tagline && (
                                <p id="tagline-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.tagline}
                                </p>
                            )}
                        </div>

                        {/* Logo URL */}
                        <div>
                            <label htmlFor="logo_url" className="mb-1 block text-sm font-medium text-gray-700">
                                Logo URL
                            </label>
                            <input
                                id="logo_url"
                                type="url"
                                maxLength={500}
                                value={data.logo_url}
                                onChange={(e) => setData('logo_url', e.target.value)}
                                placeholder="https://example.com/logo.png"
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.logo_url ? 'logo-url-error' : undefined}
                            />
                            {errors.logo_url && (
                                <p id="logo-url-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.logo_url}
                                </p>
                            )}
                            {data.logo_url && (
                                <div className="mt-3">
                                    <img
                                        src={data.logo_url}
                                        alt="Logo preview"
                                        className="h-12 object-contain"
                                        onError={(e) => {
                                            (e.target as HTMLImageElement).style.display = 'none';
                                        }}
                                    />
                                </div>
                            )}
                        </div>

                        {/* Favicon URL */}
                        <div>
                            <label htmlFor="favicon_url" className="mb-1 block text-sm font-medium text-gray-700">
                                Favicon URL
                            </label>
                            <input
                                id="favicon_url"
                                type="url"
                                maxLength={500}
                                value={data.favicon_url}
                                onChange={(e) => setData('favicon_url', e.target.value)}
                                placeholder="https://example.com/favicon.ico"
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.favicon_url ? 'favicon-url-error' : undefined}
                            />
                            {errors.favicon_url && (
                                <p id="favicon-url-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.favicon_url}
                                </p>
                            )}
                        </div>

                        {/* Colors */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="primary_color" className="mb-1 block text-sm font-medium text-gray-700">
                                    Primary Color
                                </label>
                                <div className="flex items-center gap-3">
                                    <input
                                        id="primary_color"
                                        type="color"
                                        value={data.primary_color}
                                        onChange={(e) => setData('primary_color', e.target.value)}
                                        className="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 p-1"
                                    />
                                    <input
                                        type="text"
                                        value={data.primary_color}
                                        onChange={(e) => setData('primary_color', e.target.value)}
                                        pattern="^#[0-9A-Fa-f]{6}$"
                                        maxLength={7}
                                        placeholder="#3B82F6"
                                        className="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 font-mono text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        aria-label="Primary color hex value"
                                    />
                                </div>
                                {errors.primary_color && (
                                    <p className="mt-1 text-sm text-red-600" role="alert">
                                        {errors.primary_color}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="secondary_color" className="mb-1 block text-sm font-medium text-gray-700">
                                    Secondary Color
                                </label>
                                <div className="flex items-center gap-3">
                                    <input
                                        id="secondary_color"
                                        type="color"
                                        value={data.secondary_color}
                                        onChange={(e) => setData('secondary_color', e.target.value)}
                                        className="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 p-1"
                                    />
                                    <input
                                        type="text"
                                        value={data.secondary_color}
                                        onChange={(e) => setData('secondary_color', e.target.value)}
                                        pattern="^#[0-9A-Fa-f]{6}$"
                                        maxLength={7}
                                        placeholder="#1E40AF"
                                        className="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 font-mono text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        aria-label="Secondary color hex value"
                                    />
                                </div>
                                {errors.secondary_color && (
                                    <p className="mt-1 text-sm text-red-600" role="alert">
                                        {errors.secondary_color}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Color Preview */}
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="mb-2 text-xs font-medium text-gray-500">Color Preview</p>
                            <div className="flex gap-3">
                                <div
                                    className="flex h-12 flex-1 items-center justify-center rounded-lg text-xs font-semibold text-white"
                                    style={{ backgroundColor: data.primary_color }}
                                >
                                    Primary
                                </div>
                                <div
                                    className="flex h-12 flex-1 items-center justify-center rounded-lg text-xs font-semibold text-white"
                                    style={{ backgroundColor: data.secondary_color }}
                                >
                                    Secondary
                                </div>
                            </div>
                        </div>

                        {/* Footer Text */}
                        <div>
                            <label htmlFor="footer_text" className="mb-1 block text-sm font-medium text-gray-700">
                                Footer Text
                            </label>
                            <textarea
                                id="footer_text"
                                rows={3}
                                value={data.footer_text}
                                onChange={(e) => setData('footer_text', e.target.value)}
                                placeholder="© 2026 GrowthPedia. All rights reserved."
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.footer_text ? 'footer-text-error' : undefined}
                            />
                            {errors.footer_text && (
                                <p id="footer-text-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.footer_text}
                                </p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? 'Saving…' : 'Save Branding'}
                        </button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
