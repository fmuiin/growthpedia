import type { FormEvent } from 'react';

interface PaymentFormProps {
    paymentToken: string;
    onTokenChange: (token: string) => void;
    onSubmit: (e: FormEvent) => void;
    processing: boolean;
    error?: string;
}

export default function PaymentForm({ paymentToken, onTokenChange, onSubmit, processing, error }: PaymentFormProps) {
    return (
        <form onSubmit={onSubmit} className="space-y-5">
            <div>
                <label htmlFor="payment_token" className="mb-1 block text-sm font-medium text-gray-700">
                    Payment Token
                </label>
                <input
                    id="payment_token"
                    type="text"
                    required
                    value={paymentToken}
                    onChange={(e) => onTokenChange(e.target.value)}
                    placeholder="Enter payment token"
                    className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    aria-describedby={error ? 'payment-token-error' : undefined}
                />
                {error && (
                    <p id="payment-token-error" className="mt-1 text-sm text-red-600" role="alert">
                        {error}
                    </p>
                )}
            </div>

            <button
                type="submit"
                disabled={processing}
                className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing ? 'Processing…' : 'Subscribe Now'}
            </button>
        </form>
    );
}
