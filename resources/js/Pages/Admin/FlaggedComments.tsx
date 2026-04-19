import AdminLayout from '@/Components/Layout/AdminLayout';
import { router } from '@inertiajs/react';
import type { PaginatedFlaggedCommentsType } from '@/Types/admin';

interface FlaggedCommentsProps {
    flaggedComments: PaginatedFlaggedCommentsType;
}

export default function FlaggedComments({ flaggedComments }: FlaggedCommentsProps) {
    function handlePageChange(page: number) {
        router.get('/admin/analytics/flagged-comments', { page: String(page) });
    }

    return (
        <AdminLayout>
            <div className="mx-auto max-w-6xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Flagged Comments</h1>

                {flaggedComments.comments.length === 0 ? (
                    <div className="rounded-lg border border-gray-200 bg-white p-12 text-center">
                        <p className="text-sm text-gray-500">No flagged comments to review.</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {flaggedComments.comments.map((comment) => (
                            <div
                                key={comment.id}
                                className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                            >
                                <div className="mb-3 flex flex-wrap items-start justify-between gap-2">
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm font-medium text-gray-900">
                                            {comment.authorName}
                                        </span>
                                        <span className="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                                            Flagged
                                        </span>
                                    </div>
                                    <time
                                        dateTime={comment.flaggedAt}
                                        className="text-xs text-gray-400"
                                    >
                                        {new Date(comment.flaggedAt).toLocaleString()}
                                    </time>
                                </div>

                                <p className="mb-3 text-sm text-gray-700">{comment.content}</p>

                                <div className="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                    <span>
                                        <span className="font-medium text-gray-600">Reason:</span>{' '}
                                        {comment.flagReason}
                                    </span>
                                    <span>
                                        <span className="font-medium text-gray-600">Lesson:</span>{' '}
                                        {comment.lessonTitle}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {flaggedComments.lastPage > 1 && (
                    <nav className="mt-6 flex items-center justify-between" aria-label="Flagged comments pagination">
                        <p className="text-sm text-gray-600">
                            Showing page {flaggedComments.currentPage} of {flaggedComments.lastPage} ({flaggedComments.total} total)
                        </p>
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => handlePageChange(flaggedComments.currentPage - 1)}
                                disabled={flaggedComments.currentPage <= 1}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Previous
                            </button>
                            {Array.from({ length: flaggedComments.lastPage }, (_, i) => i + 1).map((page) => (
                                <button
                                    key={page}
                                    onClick={() => handlePageChange(page)}
                                    className={`rounded-lg px-3 py-2 text-sm font-medium ${
                                        page === flaggedComments.currentPage
                                            ? 'bg-indigo-600 text-white'
                                            : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                    }`}
                                    aria-current={page === flaggedComments.currentPage ? 'page' : undefined}
                                >
                                    {page}
                                </button>
                            ))}
                            <button
                                onClick={() => handlePageChange(flaggedComments.currentPage + 1)}
                                disabled={flaggedComments.currentPage >= flaggedComments.lastPage}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    </nav>
                )}
            </div>
        </AdminLayout>
    );
}
