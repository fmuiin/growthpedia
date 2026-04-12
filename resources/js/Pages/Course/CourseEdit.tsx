import { useForm, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import type { CourseDetailType } from '@/Types/course';
import type { FormEvent } from 'react';
import { useState } from 'react';

interface CourseEditProps {
    course: CourseDetailType;
}

export default function CourseEdit({ course }: CourseEditProps) {
    const courseForm = useForm({
        title: course.title,
        description: course.description,
        category: course.category,
    });

    const moduleForm = useForm({ title: '' });

    const [addingLessonForModule, setAddingLessonForModule] = useState<number | null>(null);
    const lessonForm = useForm({
        title: '',
        content_type: 'text' as 'text' | 'video' | 'mixed',
        content_body: '',
        video_url: '',
        sort_order: '1',
    });

    function handleCourseUpdate(e: FormEvent) {
        e.preventDefault();
        courseForm.put(`/courses/${course.id}`);
    }

    function handleAddModule(e: FormEvent) {
        e.preventDefault();
        moduleForm.post(`/courses/${course.id}/modules`, {
            onSuccess: () => moduleForm.reset(),
        });
    }

    function handleAddLesson(e: FormEvent, moduleId: number) {
        e.preventDefault();
        lessonForm.post(`/modules/${moduleId}/lessons`, {
            onSuccess: () => {
                lessonForm.reset();
                setAddingLessonForModule(null);
            },
        });
    }

    function handlePublish() {
        router.post(`/courses/${course.id}/publish`);
    }

    function handleUnpublish() {
        router.post(`/courses/${course.id}/unpublish`);
    }

    function handleDeleteCourse() {
        if (confirm('Are you sure you want to delete this course?')) {
            router.delete(`/courses/${course.id}`);
        }
    }

    function handleDeleteModule(moduleId: number) {
        if (confirm('Delete this module and all its lessons?')) {
            router.delete(`/modules/${moduleId}`);
        }
    }

    function handleDeleteLesson(lessonId: number) {
        if (confirm('Delete this lesson?')) {
            router.delete(`/lessons/${lessonId}`);
        }
    }

    const sortedModules = [...course.modules].sort((a, b) => a.sortOrder - b.sortOrder);

    return (
        <AppLayout>
            <div className="mx-auto max-w-3xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Edit Course</h1>
                    <Link
                        href="/courses"
                        className="text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                        &larr; Back to courses
                    </Link>
                </div>

                {/* Course Details Form */}
                <div className="mb-8 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-gray-900">Course Details</h2>
                        <span
                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${
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

                    <form onSubmit={handleCourseUpdate} className="space-y-5">
                        <div>
                            <label htmlFor="course-title" className="mb-1 block text-sm font-medium text-gray-700">
                                Title
                            </label>
                            <input
                                id="course-title"
                                type="text"
                                required
                                value={courseForm.data.title}
                                onChange={(e) => courseForm.setData('title', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={courseForm.errors.title ? 'course-title-error' : undefined}
                            />
                            {courseForm.errors.title && (
                                <p id="course-title-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {courseForm.errors.title}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="course-description" className="mb-1 block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea
                                id="course-description"
                                required
                                rows={4}
                                value={courseForm.data.description}
                                onChange={(e) => courseForm.setData('description', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={courseForm.errors.description ? 'course-description-error' : undefined}
                            />
                            {courseForm.errors.description && (
                                <p id="course-description-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {courseForm.errors.description}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="course-category" className="mb-1 block text-sm font-medium text-gray-700">
                                Category
                            </label>
                            <input
                                id="course-category"
                                type="text"
                                required
                                value={courseForm.data.category}
                                onChange={(e) => courseForm.setData('category', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={courseForm.errors.category ? 'course-category-error' : undefined}
                            />
                            {courseForm.errors.category && (
                                <p id="course-category-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {courseForm.errors.category}
                                </p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={courseForm.processing}
                            className="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {courseForm.processing ? 'Updating…' : 'Update Course'}
                        </button>
                    </form>
                </div>

                {/* Publish / Unpublish / Delete Actions */}
                <div className="mb-8 flex flex-wrap gap-3">
                    {course.status !== 'published' ? (
                        <button
                            type="button"
                            onClick={handlePublish}
                            className="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none"
                        >
                            Publish Course
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={handleUnpublish}
                            className="rounded-xl bg-yellow-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 focus:outline-none"
                        >
                            Unpublish Course
                        </button>
                    )}

                    {course.status === 'draft' && (
                        <button
                            type="button"
                            onClick={handleDeleteCourse}
                            className="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                        >
                            Delete Course
                        </button>
                    )}
                </div>

                {/* Modules & Lessons */}
                <div className="mb-8 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">Modules &amp; Lessons</h2>

                    {sortedModules.length === 0 && (
                        <p className="mb-4 text-sm text-gray-500">No modules yet. Add one below.</p>
                    )}

                    <div className="space-y-4">
                        {sortedModules.map((mod) => {
                            const sortedLessons = [...mod.lessons].sort((a, b) => a.sortOrder - b.sortOrder);
                            return (
                                <div key={mod.id} className="rounded-lg border border-gray-200 p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <h3 className="text-sm font-semibold text-gray-900">
                                            Module {mod.sortOrder}: {mod.title}
                                        </h3>
                                        <button
                                            type="button"
                                            onClick={() => handleDeleteModule(mod.id)}
                                            className="text-xs font-medium text-red-600 hover:text-red-500"
                                        >
                                            Delete module
                                        </button>
                                    </div>

                                    {sortedLessons.length > 0 && (
                                        <ul className="mb-3 space-y-1" role="list">
                                            {sortedLessons.map((lesson) => (
                                                <li
                                                    key={lesson.id}
                                                    className="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2"
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <span className="rounded bg-indigo-100 px-1.5 py-0.5 text-xs font-medium text-indigo-700 capitalize">
                                                            {lesson.contentType}
                                                        </span>
                                                        <span className="text-sm text-gray-700">{lesson.title}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeleteLesson(lesson.id)}
                                                        className="text-xs font-medium text-red-600 hover:text-red-500"
                                                    >
                                                        Delete
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}

                                    {addingLessonForModule === mod.id ? (
                                        <form
                                            onSubmit={(e) => handleAddLesson(e, mod.id)}
                                            className="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-3"
                                        >
                                            <div>
                                                <label htmlFor={`lesson-title-${mod.id}`} className="mb-1 block text-xs font-medium text-gray-700">
                                                    Lesson Title
                                                </label>
                                                <input
                                                    id={`lesson-title-${mod.id}`}
                                                    type="text"
                                                    required
                                                    value={lessonForm.data.title}
                                                    onChange={(e) => lessonForm.setData('title', e.target.value)}
                                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label htmlFor={`lesson-type-${mod.id}`} className="mb-1 block text-xs font-medium text-gray-700">
                                                    Content Type
                                                </label>
                                                <select
                                                    id={`lesson-type-${mod.id}`}
                                                    value={lessonForm.data.content_type}
                                                    onChange={(e) => lessonForm.setData('content_type', e.target.value as 'text' | 'video' | 'mixed')}
                                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                >
                                                    <option value="text">Text</option>
                                                    <option value="video">Video</option>
                                                    <option value="mixed">Mixed</option>
                                                </select>
                                            </div>
                                            {(lessonForm.data.content_type === 'text' || lessonForm.data.content_type === 'mixed') && (
                                                <div>
                                                    <label htmlFor={`lesson-body-${mod.id}`} className="mb-1 block text-xs font-medium text-gray-700">
                                                        Content Body
                                                    </label>
                                                    <textarea
                                                        id={`lesson-body-${mod.id}`}
                                                        rows={3}
                                                        value={lessonForm.data.content_body}
                                                        onChange={(e) => lessonForm.setData('content_body', e.target.value)}
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                    />
                                                </div>
                                            )}
                                            {(lessonForm.data.content_type === 'video' || lessonForm.data.content_type === 'mixed') && (
                                                <div>
                                                    <label htmlFor={`lesson-video-${mod.id}`} className="mb-1 block text-xs font-medium text-gray-700">
                                                        Video URL
                                                    </label>
                                                    <input
                                                        id={`lesson-video-${mod.id}`}
                                                        type="url"
                                                        value={lessonForm.data.video_url}
                                                        onChange={(e) => lessonForm.setData('video_url', e.target.value)}
                                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                    />
                                                </div>
                                            )}
                                            <div>
                                                <label htmlFor={`lesson-order-${mod.id}`} className="mb-1 block text-xs font-medium text-gray-700">
                                                    Sort Order
                                                </label>
                                                <input
                                                    id={`lesson-order-${mod.id}`}
                                                    type="number"
                                                    min="1"
                                                    required
                                                    value={lessonForm.data.sort_order}
                                                    onChange={(e) => lessonForm.setData('sort_order', e.target.value)}
                                                    className="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                                />
                                            </div>
                                            <div className="flex gap-2">
                                                <button
                                                    type="submit"
                                                    disabled={lessonForm.processing}
                                                    className="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                                >
                                                    {lessonForm.processing ? 'Adding…' : 'Add Lesson'}
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => { setAddingLessonForModule(null); lessonForm.reset(); }}
                                                    className="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-800"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => { setAddingLessonForModule(mod.id); lessonForm.reset(); }}
                                            className="text-xs font-medium text-indigo-600 hover:text-indigo-500"
                                        >
                                            + Add Lesson
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    {/* Add Module Form */}
                    <form onSubmit={handleAddModule} className="mt-4 flex items-end gap-3">
                        <div className="flex-1">
                            <label htmlFor="module-title" className="mb-1 block text-sm font-medium text-gray-700">
                                New Module Title
                            </label>
                            <input
                                id="module-title"
                                type="text"
                                required
                                value={moduleForm.data.title}
                                onChange={(e) => moduleForm.setData('title', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={moduleForm.errors.title ? 'module-title-error' : undefined}
                            />
                            {moduleForm.errors.title && (
                                <p id="module-title-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {moduleForm.errors.title}
                                </p>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={moduleForm.processing}
                            className="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {moduleForm.processing ? 'Adding…' : 'Add Module'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
