import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import PlanCard from '@/Components/Subscription/PlanCard';
import PaymentForm from '@/Components/Subscription/PaymentForm';
import type { MembershipPlanType } from '@/Types/subscription';
import type { FormEvent } from 'react';

interface CheckoutProps {
    plan: MembershipPlanType;
}

export default function Checkout({ plan }: CheckoutProps) {
    const { data, setData, post, processing, errors } = useForm({
        plan_id: plan.id,
        payment_token: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/subscribe');
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-2xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Checkout</h1>
                    <Link
                        href="/plans"
                        className="text-sm font-medium text-gray-500 hover:text-gray-700"
                    >
                        &larr; Back to plans
                    </Link>
                </div>

                <div className="mb-6">
                    <PlanCard plan={plan} showSelectButton={false} />
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 className="mb-4 text-lg font-semibold text-gray-900">Payment Details</h2>
                    <PaymentForm
                        paymentToken={data.payment_token}
                        onTokenChange={(token) => setData('payment_token', token)}
                        onSubmit={handleSubmit}
                        processing={processing}
                        error={errors.payment_token}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
