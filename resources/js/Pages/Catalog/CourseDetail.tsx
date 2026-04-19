import AppLayout from '@/Components/Layout/AppLayout';
import { Link } from '@inertiajs/react';
import type { CatalogCourseDetailType } from '@/Types/catalog';

interface CourseDetailProps {
    course: CatalogCourseDetailType;
}

export default function CourseDetail({ course }: CourseDetailProps) {
    const totalLessons = course.modules.reduce((sum, mod) => sum + mod.lessons.length, 0);

    return (
        <AppLayout>
            <div className="mx-auto max-w-4xl">
                <Link
                    href="/catalog"
                    className="mb-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    ← Back to Catalog
                </Link>

                {/* Course header */}
                <div className="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="mb-3">
                        <span className="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                            {course.category}
                        </span>
                    </div>
                    <h1 className="mb-3 text-3xl font-bold text-gray-900">{course.title}</h1>
                    <p className="mb-4 text-gray-600 leading-relaxed">{course.description}</p>

                    <div className="flex flex-wrap items-center gap-6 text-sm text-gray-500">
                        <div className="flex items-center gap-1.5">
                            <span className="font-medium text-gray-700">Instructor:</span>
                            <span>{course.instructorName}</span>
                        </div>
                        {course.instructorBio && (
                            <div className="text-xs text-gray-400">— {course.instructorBio}</div>
                        )}
                        <div className="flex items-center gap-1.5">
                            <span className="font-medium text-gray-700">Enrolled:</span>
                            <span>{course.enrollmentCount.toLocaleString()} {course.enrollmentCount === 1 ? 'learner' : 'learners'}</span>
                        </div>
                        {course.averageRating !== null && (
                            <div className="flex items-center gap-1.5">
                                <span className="font-medium text-gray-700">Rating:</span>
                                <span>{course.averageRating.toFixed(1)} / 5</span>
                            </div>
                        )}
                        <div className="flex items-center gap-1.5">
                            <span className="font-medium text-gray-700">Published:</span>
                            <time dateTime={course.publishedAt}>
                                {new Date(course.publishedAt).toLocaleDateString()}
                            </time>
                        </div>
                    </div>
                </div>

                {/* Course structure */}
                <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="mb-1 text-xl font-semibold text-gray-900">Course Content</h2>
                    <p className="mb-5 text-sm text-gray-500">
                        {course.modules.length} {course.modules.length === 1 ? 'module' : 'modules'} · {totalLessons} {totalLessons === 1 ? 'lesson' : 'lessons'}
                    </p>

                    {course.modules.length === 0 ? (
                        <p className="text-sm text-gray-500">No content available yet.</p>
                    ) : (
                        <div className="space-y-4">
                            {course.modules.map((mod, modIndex) => (
                                <details key={mod.id} className="group rounded-lg border border-gray-100" open={modIndex === 0}>
                                    <summary className="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 hover:bg-gray-100">
                                        <span>
                                            Module {modIndex + 1}: {mod.title}
                                        </span>
                                        <span className="text-xs text-gray-500">
                                            {mod.lessons.length} {mod.lessons.length === 1 ? 'lesson' : 'lessons'}
                                        </span>
                                    </summary>
                                    <ul className="divide-y divide-gray-100 px-4">
                                        {mod.lessons.map((lesson, lessonIndex) => (
                                            <li key={lesson.id} className="flex items-center gap-3 py-3">
                                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-600">
                                                    {lessonIndex + 1}
                                                </span>
                                                <span className="text-sm text-gray-700">{lesson.title}</span>
                                                <span className="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 capitalize">
                                                    {lesson.contentType}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </details>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
