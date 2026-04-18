import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import type { CommentType, PaginatedCommentsType } from '@/Types/discussion';
import CommentForm from '@/Components/Discussion/CommentForm';
import FlagButton from '@/Components/Discussion/FlagButton';

interface CommentThreadProps {
    lessonId: number;
    comments: PaginatedCommentsType;
    canComment: boolean;
}

function EditForm({ comment, onCancel }: { comment: CommentType; onCancel: () => void }) {
    const { data, setData, put, processing, errors } = useForm({
        content: comment.content,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        put(`/comments/${comment.id}`, {
            onSuccess: () => onCancel(),
        });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-2">
            <textarea
                value={data.content}
                onChange={(e) => setData('content', e.target.value)}
                rows={2}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                aria-label="Edit comment"
            />
            {errors.content && (
                <p className="text-xs text-red-600">{errors.content}</p>
            )}
            <div className="flex items-center gap-2">
                <button
                    type="submit"
                    disabled={processing || data.content.trim() === ''}
                    className="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {processing ? 'Saving...' : 'Save'}
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="text-xs text-gray-500 hover:text-gray-700"
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}

function CommentItem({
    comment,
    lessonId,
    canComment,
    currentUserId,
}: {
    comment: CommentType;
    lessonId: number;
    canComment: boolean;
    currentUserId: number | null;
}) {
    const [showReplyForm, setShowReplyForm] = useState(false);
    const [isEditing, setIsEditing] = useState(false);

    const isAuthor = currentUserId !== null && currentUserId === comment.userId;

    return (
        <div className="space-y-3">
            <div className="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-900">{comment.authorName}</span>
                    <span className="text-xs text-gray-400">
                        {new Date(comment.createdAt).toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                        })}
                    </span>
                    {comment.isEdited && (
                        <span className="text-xs italic text-gray-400">(edited)</span>
                    )}
                </div>

                {isEditing ? (
                    <div className="mt-2">
                        <EditForm comment={comment} onCancel={() => setIsEditing(false)} />
                    </div>
                ) : (
                    <p className="mt-1 text-sm text-gray-700 whitespace-pre-wrap">{comment.content}</p>
                )}

                {!isEditing && (
                    <div className="mt-2 flex items-center gap-3">
                        {canComment && (
                            <button
                                type="button"
                                onClick={() => setShowReplyForm(!showReplyForm)}
                                className="text-xs text-indigo-600 hover:text-indigo-800"
                            >
                                {showReplyForm ? 'Cancel Reply' : 'Reply'}
                            </button>
                        )}
                        {isAuthor && (
                            <button
                                type="button"
                                onClick={() => setIsEditing(true)}
                                className="text-xs text-gray-400 hover:text-gray-600"
                            >
                                Edit
                            </button>
                        )}
                        <FlagButton commentId={comment.id} />
                    </div>
                )}
            </div>

            {showReplyForm && canComment && (
                <div className="ml-6">
                    <CommentForm
                        lessonId={lessonId}
                        parentCommentId={comment.id}
                        onCancel={() => setShowReplyForm(false)}
                    />
                </div>
            )}

            {comment.replies.length > 0 && (
                <div className="ml-6 space-y-3 border-l-2 border-gray-100 pl-4">
                    {comment.replies.map((reply) => (
                        <CommentItem
                            key={reply.id}
                            comment={reply}
                            lessonId={lessonId}
                            canComment={canComment}
                            currentUserId={currentUserId}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function CommentThread({ lessonId, comments, canComment }: CommentThreadProps) {
    // Try to get current user id from page props
    let currentUserId: number | null = null;
    try {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const page = (window as any).__page;
        currentUserId = page?.props?.auth?.user?.id ?? null;
    } catch {
        // ignore
    }

    function handlePageChange(page: number) {
        router.visit(window.location.pathname, {
            data: { comment_page: page },
            preserveState: true,
            preserveScroll: true,
        });
    }

    return (
        <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900">Discussion</h2>

            {canComment ? (
                <CommentForm lessonId={lessonId} />
            ) : (
                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Active subscription required to post comments.
                </div>
            )}

            {comments.comments.length === 0 ? (
                <p className="text-sm text-gray-500">No comments yet. Be the first to start the discussion.</p>
            ) : (
                <div className="space-y-4">
                    {comments.comments.map((comment) => (
                        <CommentItem
                            key={comment.id}
                            comment={comment}
                            lessonId={lessonId}
                            canComment={canComment}
                            currentUserId={currentUserId}
                        />
                    ))}
                </div>
            )}

            {comments.lastPage > 1 && (
                <nav className="flex items-center justify-center gap-2" aria-label="Comment pagination">
                    <button
                        type="button"
                        onClick={() => handlePageChange(comments.currentPage - 1)}
                        disabled={comments.currentPage <= 1}
                        className="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span className="text-sm text-gray-500">
                        Page {comments.currentPage} of {comments.lastPage}
                    </span>
                    <button
                        type="button"
                        onClick={() => handlePageChange(comments.currentPage + 1)}
                        disabled={comments.currentPage >= comments.lastPage}
                        className="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                    >
                        Next
                    </button>
                </nav>
            )}
        </div>
    );
}
