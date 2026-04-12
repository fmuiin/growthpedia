import { Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import type { CourseType } from '@/Types/course';

interface CourseListProps {
    courses: CourseType[];
}

export default function CourseList({ courses }: CourseListProps) {
    function formatDate(dateStr: string) {
        return new Date(dateStr).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    return (
        <AppLayout>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">My Courses</h1>
                <Link
                    href="/courses/create"
                    className="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                    Create New Course
                </Link>
            </div>

            {courses.length === 0 ? (
                <div className="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200">
                    <p className="text-gray-500">You haven&apos;t created any courses yet.</p>
                    <Link
                        href="/courses/create"
                        className="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Create your first course &rarr;
                    </Link>
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {courses.map((course) => (
                        <Link
                            key={course.id}
                            href={`/courses/${course.id}/edit`}
                            className="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md"
                        >
                            <div className="mb-3 flex items-start justify-between">
                                <h2 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600">
                                    {course.title}
                                </h2>
                                <span
                                    className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                                        course.status === 'published'
                                            ? 'bg-green-100 text-green-700'
                                            : course.status === 'draft'
                                              ? 'bg-yellow-100 text-yellow-700'
                                              : 'bg-gray-100 text-gray-700'
                                    }`}
                                >
                                    {course.status}
                                </span>
                            </div>
                            <p className="mb-3 line-clamp-2 text-sm text-gray-500">
                                {course.description}
                            </p>
                            <div className="flex items-center justify-between text-xs text-gray-400">
                                <span className="rounded bg-gray-100 px-2 py-0.5 text-gray-600">
                                    {course.category}
                                </span>
                                <span>{formatDate(course.createdAt)}</span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
