import AdminLayout from '@/Components/Layout/AdminLayout';
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { CreatorProfileType } from '@/Types/branding';

interface CreatorProfileEditProps {
    profile: CreatorProfileType;
}

const SOCIAL_KEYS = ['twitter', 'linkedin', 'youtube', 'website'] as const;

const SOCIAL_LABELS: Record<string, string> = {
    twitter: 'Twitter / X',
    linkedin: 'LinkedIn',
    youtube: 'YouTube',
    website: 'Website',
};

const SOCIAL_PLACEHOLDERS: Record<string, string> = {
    twitter: 'https://twitter.com/yourhandle',
    linkedin: 'https://linkedin.com/in/yourprofile',
    youtube: 'https://youtube.com/@yourchannel',
    website: 'https://yourwebsite.com',
};

export default function CreatorProfileEdit({ profile }: CreatorProfileEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        display_name: profile.displayName ?? '',
        bio: profile.bio ?? '',
        avatar_url: profile.avatarUrl ?? '',
        expertise: profile.expertise ?? '',
        social_links: profile.socialLinks ?? {},
        featured_course_ids: profile.featuredCourseIds ?? [],
    });

    const [featuredInput, setFeaturedInput] = useState(
        (profile.featuredCourseIds ?? []).join(', '),
    );

    function handleSocialLinkChange(key: string, value: string) {
        setData('social_links', { ...data.social_links, [key]: value });
    }

    function handleFeaturedCoursesChange(value: string) {
        setFeaturedInput(value);
        const ids = value
            .split(',')
            .map((s) => s.trim())
            .filter((s) => s !== '' && !isNaN(Number(s)))
            .map(Number);
        setData('featured_course_ids', ids);
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        put('/admin/branding/profile');
    }

    return (
        <AdminLayout>
            <div className="mx-auto max-w-2xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Creator Profile</h1>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Display Name */}
                        <div>
                            <label htmlFor="display_name" className="mb-1 block text-sm font-medium text-gray-700">
                                Display Name
                            </label>
                            <input
                                id="display_name"
                                type="text"
                                required
                                maxLength={255}
                                value={data.display_name}
                                onChange={(e) => setData('display_name', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.display_name ? 'display-name-error' : undefined}
                            />
                            {errors.display_name && (
                                <p id="display-name-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.display_name}
                                </p>
                            )}
                        </div>

                        {/* Bio */}
                        <div>
                            <label htmlFor="bio" className="mb-1 block text-sm font-medium text-gray-700">
                                Bio
                            </label>
                            <textarea
                                id="bio"
                                rows={4}
                                maxLength={5000}
                                value={data.bio}
                                onChange={(e) => setData('bio', e.target.value)}
                                placeholder="Tell your audience about yourself..."
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.bio ? 'bio-error' : undefined}
                            />
                            <p className="mt-1 text-xs text-gray-400">{data.bio.length} / 5000</p>
                            {errors.bio && (
                                <p id="bio-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.bio}
                                </p>
                            )}
                        </div>

                        {/* Avatar URL */}
                        <div>
                            <label htmlFor="avatar_url" className="mb-1 block text-sm font-medium text-gray-700">
                                Avatar URL
                            </label>
                            <input
                                id="avatar_url"
                                type="url"
                                maxLength={500}
                                value={data.avatar_url}
                                onChange={(e) => setData('avatar_url', e.target.value)}
                                placeholder="https://example.com/avatar.jpg"
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.avatar_url ? 'avatar-url-error' : undefined}
                            />
                            {errors.avatar_url && (
                                <p id="avatar-url-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.avatar_url}
                                </p>
                            )}
                            {data.avatar_url && (
                                <div className="mt-3">
                                    <img
                                        src={data.avatar_url}
                                        alt="Avatar preview"
                                        className="h-16 w-16 rounded-full object-cover ring-2 ring-gray-200"
                                        onError={(e) => {
                                            (e.target as HTMLImageElement).style.display = 'none';
                                        }}
                                    />
                                </div>
                            )}
                        </div>

                        {/* Expertise */}
                        <div>
                            <label htmlFor="expertise" className="mb-1 block text-sm font-medium text-gray-700">
                                Expertise
                            </label>
                            <input
                                id="expertise"
                                type="text"
                                maxLength={255}
                                value={data.expertise}
                                onChange={(e) => setData('expertise', e.target.value)}
                                placeholder="e.g. Full-Stack Development, Digital Marketing"
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.expertise ? 'expertise-error' : undefined}
                            />
                            {errors.expertise && (
                                <p id="expertise-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.expertise}
                                </p>
                            )}
                        </div>

                        {/* Social Links */}
                        <fieldset>
                            <legend className="mb-3 text-sm font-medium text-gray-700">Social Links</legend>
                            <div className="space-y-3">
                                {SOCIAL_KEYS.map((key) => (
                                    <div key={key}>
                                        <label htmlFor={`social-${key}`} className="mb-1 block text-xs font-medium text-gray-500">
                                            {SOCIAL_LABELS[key]}
                                        </label>
                                        <input
                                            id={`social-${key}`}
                                            type="url"
                                            value={data.social_links[key] ?? ''}
                                            onChange={(e) => handleSocialLinkChange(key, e.target.value)}
                                            placeholder={SOCIAL_PLACEHOLDERS[key]}
                                            className="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        />
                                    </div>
                                ))}
                            </div>
                            {errors.social_links && (
                                <p className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.social_links}
                                </p>
                            )}
                        </fieldset>

                        {/* Featured Course IDs */}
                        <div>
                            <label htmlFor="featured_course_ids" className="mb-1 block text-sm font-medium text-gray-700">
                                Featured Course IDs
                            </label>
                            <input
                                id="featured_course_ids"
                                type="text"
                                value={featuredInput}
                                onChange={(e) => handleFeaturedCoursesChange(e.target.value)}
                                placeholder="e.g. 1, 3, 5"
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby="featured-courses-hint"
                            />
                            <p id="featured-courses-hint" className="mt-1 text-xs text-gray-400">
                                Comma-separated list of published course IDs to feature on your profile.
                            </p>
                            {errors.featured_course_ids && (
                                <p className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.featured_course_ids}
                                </p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? 'Saving…' : 'Save Profile'}
                        </button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
