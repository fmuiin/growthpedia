import AppLayout from '@/Components/Layout/AppLayout';
import { Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { CatalogFilters, PaginatedCoursesType } from '@/Types/catalog';

interface CatalogIndexProps {
    courses: PaginatedCoursesType;
    filters: CatalogFilters;
    categories: string[];
    searchQuery?: string;
}

export default function CatalogIndex({ courses, filters, categories, searchQuery = '' }: CatalogIndexProps) {
    const [search, setSearch] = useState(searchQuery);

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        if (search.trim() === '') {
            router.get('/catalog');
            return;
        }
        router.get('/catalog/search', { q: search.trim() });
    }

    function handleCategoryChange(category: string) {
        const params: Record<string, string> = {};
        if (category !== '') {
            params.category = category;
        }
        if (filters.sort) {
            params.sort = filters.sort;
        }
        router.get('/catalog', params);
    }

    function handleSortChange(sort: string) {
        const params: Record<string, string> = {};
        if (filters.category) {
            params.category = filters.category;
        }
        if (sort !== '') {
            params.sort = sort;
        }
        router.get('/catalog', params);
    }

    function handlePageChange(page: number) {
        if (searchQuery) {
            router.get('/catalog/search', { q: searchQuery, page: String(page) });
        } else {
            const params: Record<string, string> = { page: String(page) };
            if (filters.category) params.category = filters.category;
            if (filters.sort) params.sort = filters.sort;
            router.get('/catalog', params);
        }
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-6xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Course Catalog</h1>

                {/* Search bar */}
                <form onSubmit={handleSearch} className="mb-6">
                    <div className="flex gap-3">
                        <label htmlFor="catalog-search" className="sr-only">
                            Search courses
                        </label>
                        <input
                            id="catalog-search"
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search courses by title, description, or category..."
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <button
                            type="submit"
                            className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Search
                        </button>
                    </div>
                </form>

                {/* Filters */}
                {!searchQuery && (
                    <div className="mb-6 flex flex-wrap items-center gap-4">
                        <div>
                            <label htmlFor="category-filter" className="mr-2 text-sm font-medium text-gray-700">
                                Category:
                            </label>
                            <select
                                id="category-filter"
                                value={filters.category ?? ''}
                                onChange={(e) => handleCategoryChange(e.target.value)}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Categories</option>
                                {categories.map((cat) => (
                                    <option key={cat} value={cat}>
                                        {cat}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="sort-filter" className="mr-2 text-sm font-medium text-gray-700">
                                Sort by:
                            </label>
                            <select
                                id="sort-filter"
                                value={filters.sort ?? ''}
                                onChange={(e) => handleSortChange(e.target.value)}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Most Recent</option>
                                <option value="title">Title (A-Z)</option>
                                <option value="oldest">Oldest First</option>
                            </select>
                        </div>
                    </div>
                )}

                {/* Search result indicator */}
                {searchQuery && (
                    <div className="mb-4 flex items-center gap-2">
                        <p className="text-sm text-gray-600">
                            Showing results for <span className="font-semibold">"{searchQuery}"</span>
                            {' '}({courses.total} {courses.total === 1 ? 'course' : 'courses'} found)
                        </p>
                        <Link
                            href="/catalog"
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Clear search
                        </Link>
                    </div>
                )}

                {/* Course grid */}
                {courses.data.length === 0 ? (
                    <div className="rounded-lg border border-gray-200 bg-white p-12 text-center">
                        <p className="text-sm text-gray-500">
                            {searchQuery
                                ? 'No courses match your search. Try a different query.'
                                : 'No courses available at the moment.'}
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {courses.data.map((course) => (
                            <Link
                                key={course.id}
                                href={`/catalog/${course.id}`}
                                className="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                            >
                                <div className="mb-2">
                                    <span className="inline-block rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                        {course.category}
                                    </span>
                                </div>
                                <h2 className="mb-1 text-lg font-semibold text-gray-900 group-hover:text-indigo-600">
                                    {course.title}
                                </h2>
                                <p className="mb-3 text-sm text-gray-600 line-clamp-3">
                                    {course.descriptionSummary}
                                </p>
                                <div className="flex items-center justify-between text-xs text-gray-500">
                                    <span>By {course.instructorName}</span>
                                    <time dateTime={course.publishedAt}>
                                        {new Date(course.publishedAt).toLocaleDateString()}
                                    </time>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {courses.lastPage > 1 && (
                    <nav className="mt-8 flex items-center justify-center gap-2" aria-label="Pagination">
                        <button
                            onClick={() => handlePageChange(courses.currentPage - 1)}
                            disabled={courses.currentPage <= 1}
                            className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Previous
                        </button>
                        {Array.from({ length: courses.lastPage }, (_, i) => i + 1).map((page) => (
                            <button
                                key={page}
                                onClick={() => handlePageChange(page)}
                                className={`rounded-lg px-3 py-2 text-sm font-medium ${
                                    page === courses.currentPage
                                        ? 'bg-indigo-600 text-white'
                                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                }`}
                                aria-current={page === courses.currentPage ? 'page' : undefined}
                            >
                                {page}
                            </button>
                        ))}
                        <button
                            onClick={() => handlePageChange(courses.currentPage + 1)}
                            disabled={courses.currentPage >= courses.lastPage}
                            className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Next
                        </button>
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}
