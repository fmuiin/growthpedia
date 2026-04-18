import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

interface FlagButtonProps {
    commentId: number;
}

export default function FlagButton({ commentId }: FlagButtonProps) {
    const [isOpen, setIsOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        reason: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        post(`/comments/${commentId}/flag`, {
            onSuccess: () => {
                reset('reason');
                setIsOpen(false);
            },
        });
    }

    if (!isOpen) {
        return (
            <button
                type="button"
                onClick={() => setIsOpen(true)}
                className="text-xs text-gray-400 hover:text-red-500"
                aria-label="Flag comment"
            >
                Flag
            </button>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
            <label htmlFor={`flag-reason-${commentId}`} className="mb-1 block text-xs font-medium text-gray-700">
                Reason for flagging
            </label>
            <input
                id={`flag-reason-${commentId}`}
                type="text"
                value={data.reason}
                onChange={(e) => setData('reason', e.target.value)}
                placeholder="Enter reason..."
                className="w-full rounded border border-gray-300 px-2 py-1 text-xs text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            />
            {errors.reason && (
                <p className="mt-1 text-xs text-red-600">{errors.reason}</p>
            )}
            <div className="mt-2 flex items-center gap-2">
                <button
                    type="submit"
                    disabled={processing || data.reason.trim() === ''}
                    className="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                >
                    {processing ? 'Flagging...' : 'Submit Flag'}
                </button>
                <button
                    type="button"
                    onClick={() => {
                        setIsOpen(false);
                        reset('reason');
                    }}
                    className="text-xs text-gray-500 hover:text-gray-700"
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}
