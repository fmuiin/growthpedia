import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface CommentFormProps {
    lessonId: number;
    parentCommentId?: number;
    onCancel?: () => void;
}

export default function CommentForm({ lessonId, parentCommentId, onCancel }: CommentFormProps) {
    const isReply = parentCommentId !== undefined;

    const { data, setData, post, processing, errors, reset } = useForm({
        content: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        const url = isReply
            ? `/comments/${parentCommentId}/reply`
            : `/lessons/${lessonId}/comments`;

        post(url, {
            onSuccess: () => {
                reset('content');
                onCancel?.();
            },
        });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <div>
                <label htmlFor={`comment-${parentCommentId ?? 'new'}`} className="sr-only">
                    {isReply ? 'Write a reply' : 'Write a comment'}
                </label>
                <textarea
                    id={`comment-${parentCommentId ?? 'new'}`}
                    value={data.content}
                    onChange={(e) => setData('content', e.target.value)}
                    placeholder={isReply ? 'Write a reply...' : 'Write a comment...'}
                    rows={isReply ? 2 : 3}
                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                />
                {errors.content && (
                    <p className="mt-1 text-xs text-red-600">{errors.content}</p>
                )}
            </div>

            <div className="flex items-center gap-2">
                <button
                    type="submit"
                    disabled={processing || data.content.trim() === ''}
                    className="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {processing ? 'Posting...' : isReply ? 'Reply' : 'Post Comment'}
                </button>
                {onCancel && (
                    <button
                        type="button"
                        onClick={onCancel}
                        className="rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800"
                    >
                        Cancel
                    </button>
                )}
            </div>
        </form>
    );
}
