import { Link } from '@inertiajs/react';
import type { FeaturedCourseType, PlatformBrandingType } from '@/Types/branding';

const COURSE_ACCENTS = [
    'from-violet-500 to-purple-600',
    'from-blue-500 to-indigo-600',
    'from-emerald-500 to-teal-600',
    'from-orange-500 to-amber-600',
    'from-pink-500 to-rose-600',
    'from-cyan-500 to-blue-600',
] as const;

const COURSE_ICONS = ['◆', '◇', '○', '△', '□', '◎'] as const;

interface FeaturedCoursesProps {
    courses: FeaturedCourseType[];
    creatorName: string | null;
    branding: PlatformBrandingType;
}

export default function FeaturedCourses({ courses, creatorName, branding }: FeaturedCoursesProps) {
    if (courses.length === 0) {
        return (
            <div className="py-20">
                <div className="mx-auto max-w-6xl px-4 text-center sm:px-6 lg:px-8">
                    <p className="text-xs font-semibold uppercase tracking-wider text-violet-600">
                        Our courses
                    </p>
                    <h2 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Featured courses
                    </h2>
                    <p className="mx-auto mt-6 max-w-md text-gray-600">
                        No featured courses yet. Browse our full catalog to discover what&apos;s available.
                    </p>
                    <div className="mt-8">
                        <Link
                            href="/catalog"
                            className="inline-flex rounded-full border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-50"
                        >
                            Browse our catalog
                        </Link>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="py-20">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div className="mt-0 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {courses.map((course, index) => {
                        const href = `/catalog/${course.id}`;
                        const accent = COURSE_ACCENTS[index % COURSE_ACCENTS.length];
                        const icon = COURSE_ICONS[index % COURSE_ICONS.length];

                        return (
                            <Link
                                key={`${course.id}-${index}`}
                                href={href}
                                className="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-violet-200 hover:shadow-md"
                            >
                                <div
                                    className={`relative flex h-36 items-center justify-center bg-gradient-to-br ${accent} text-4xl transition group-hover:opacity-95`}
                                >
                                    <span className="drop-shadow-sm" aria-hidden>
                                        {icon}
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
                                    <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 text-xs text-gray-500">
                                        <span>{creatorName ?? branding.siteName}</span>
                                        <span className="font-medium text-violet-600">View course →</span>
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>
                <div className="mt-12 text-center">
                    <Link
                        href="/catalog"
                        className="inline-flex rounded-full border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-50"
                    >
                        View all courses
                    </Link>
                </div>
            </div>
        </div>
    );
}
