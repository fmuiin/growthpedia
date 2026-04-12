import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import type { FormEvent } from 'react';

export default function CourseCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        category: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/courses');
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-2xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Create New Course</h1>
                    <Link
                        href="/courses"
                        className="text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                        &larr; Back to courses
                    </Link>
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div>
                            <label htmlFor="title" className="mb-1 block text-sm font-medium text-gray-700">
                                Course Title
                            </label>
                            <input
                                id="title"
                                type="text"
                                required
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.title ? 'title-error' : undefined}
                            />
                            {errors.title && (
                                <p id="title-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.title}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="description" className="mb-1 block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea
                                id="description"
                                required
                                rows={4}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.description ? 'description-error' : undefined}
                            />
                            {errors.description && (
                                <p id="description-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.description}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="category" className="mb-1 block text-sm font-medium text-gray-700">
                                Category
                            </label>
                            <input
                                id="category"
                                type="text"
                                required
                                value={data.category}
                                onChange={(e) => setData('category', e.target.value)}
                                className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                aria-describedby={errors.category ? 'category-error' : undefined}
                            />
                            {errors.category && (
                                <p id="category-error" className="mt-1 text-sm text-red-600" role="alert">
                                    {errors.category}
                                </p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? 'Creating…' : 'Create Course'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
