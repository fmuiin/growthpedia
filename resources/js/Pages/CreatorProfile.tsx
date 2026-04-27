import LandingLayout from '@/Components/Layout/LandingLayout';
import { Link } from '@inertiajs/react';
import type { CreatorProfileType, FeaturedCourseType } from '@/Types/branding';

interface CreatorProfileProps {
    profile: CreatorProfileType;
    featuredCourses: FeaturedCourseType[];
}

const SOCIAL_ICONS: Record<string, { label: string; icon: string }> = {
    twitter: { label: 'Twitter / X', icon: '𝕏' },
    linkedin: { label: 'LinkedIn', icon: 'in' },
    youtube: { label: 'YouTube', icon: '▶' },
    website: { label: 'Website', icon: '🌐' },
};

const COURSE_ACCENTS = [
    'from-violet-500 to-purple-600',
    'from-blue-500 to-indigo-600',
    'from-emerald-500 to-teal-600',
    'from-orange-500 to-amber-600',
    'from-pink-500 to-rose-600',
    'from-cyan-500 to-blue-600',
] as const;

export default function CreatorProfile({ profile, featuredCourses }: CreatorProfileProps) {
    const socialEntries = profile.socialLinks
        ? Object.entries(profile.socialLinks).filter(([, url]) => url && url.trim() !== '')
        : [];

    return (
        <LandingLayout>
            {/* Hero / Profile Header */}
            <section className="relative overflow-hidden bg-white">
                <div
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(124,58,237,0.08),transparent)]"
                    aria-hidden
                />
                <div className="relative mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:px-8">
                    {/* Avatar */}
                    {profile.avatarUrl ? (
                        <img
                            src={profile.avatarUrl}
                            alt={`${profile.displayName}'s avatar`}
                            className="mx-auto h-28 w-28 rounded-full object-cover ring-4 ring-white shadow-lg"
                        />
                    ) : (
                        <div
                            className="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 text-3xl font-bold text-white shadow-lg"
                            aria-hidden="true"
                        >
                            {profile.displayName.charAt(0).toUpperCase()}
                        </div>
                    )}

                    {/* Name */}
                    <h1 className="mt-6 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        {profile.displayName}
                    </h1>

                    {/* Expertise */}
                    {profile.expertise && (
                        <p className="mt-2 text-base font-medium text-violet-600">
                            {profile.expertise}
                        </p>
                    )}

                    {/* Bio */}
                    {profile.bio && (
                        <p className="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-gray-600">
                            {profile.bio}
                        </p>
                    )}

                    {/* Social Links */}
                    {socialEntries.length > 0 && (
                        <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                            {socialEntries.map(([key, url]) => {
                                const social = SOCIAL_ICONS[key];
                                if (!social) return null;
                                return (
                                    <a
                                        key={key}
                                        href={url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-violet-200 hover:text-violet-700"
                                        aria-label={social.label}
                                    >
                                        <span aria-hidden="true">{social.icon}</span>
                                        {social.label}
                                    </a>
                                );
                            })}
                        </div>
                    )}
                </div>
            </section>

            {/* Featured Courses */}
            {featuredCourses.length > 0 && (
                <section className="border-t border-gray-100 bg-gray-50/50 py-16">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <h2 className="text-center text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                            Featured Courses
                        </h2>
                        <p className="mx-auto mt-2 max-w-xl text-center text-gray-600">
                            Handpicked courses by {profile.displayName}
                        </p>

                        <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {featuredCourses.map((course, index) => {
                                const accent = COURSE_ACCENTS[index % COURSE_ACCENTS.length];
                                return (
                                    <Link
                                        key={course.id}
                                        href={`/catalog/${course.id}`}
                                        className="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-violet-200 hover:shadow-md"
                                    >
                                        <div
                                            className={`relative flex h-32 items-center justify-center bg-gradient-to-br ${accent} text-3xl transition group-hover:opacity-95`}
                                        >
                                            <span className="drop-shadow-sm" aria-hidden>
                                                {['◆', '◇', '○', '△', '□', '◎'][index % 6]}
                                            </span>
                                        </div>
                                        <div className="flex flex-1 flex-col p-5">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-violet-600">
                                                {course.category}
                                            </p>
                                            <h3 className="mt-2 text-lg font-bold text-gray-900 group-hover:text-violet-700">
                                                {course.title}
                                            </h3>
                                            <p className="mt-2 line-clamp-2 flex-1 text-sm text-gray-600">
                                                {course.description}
                                            </p>
                                            <div className="mt-4 border-t border-gray-100 pt-4 text-right">
                                                <span className="text-xs font-medium text-violet-600">View course →</span>
                                            </div>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>

                        <div className="mt-10 text-center">
                            <Link
                                href="/catalog"
                                className="inline-flex rounded-full border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-50"
                            >
                                View all courses
                            </Link>
                        </div>
                    </div>
                </section>
            )}

            {/* CTA */}
            <section className="bg-gray-950 py-16 text-white">
                <div className="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">
                        Ready to learn with {profile.displayName}?
                    </h2>
                    <p className="mt-3 text-gray-400">
                        Browse the full catalog and start your learning journey today.
                    </p>
                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                        <Link
                            href="/register"
                            className="inline-flex w-full min-w-[160px] items-center justify-center rounded-full bg-violet-600 px-8 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-violet-500 sm:w-auto"
                        >
                            Get started
                        </Link>
                        <Link
                            href="/catalog"
                            className="inline-flex w-full min-w-[160px] items-center justify-center rounded-full border border-white/30 bg-transparent px-8 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto"
                        >
                            Browse courses
                        </Link>
                    </div>
                </div>
            </section>
        </LandingLayout>
    );
}
