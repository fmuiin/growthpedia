import { useForm, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import type { MembershipPlanType, SubscriptionType } from '@/Types/subscription';
import type { FormEvent } from 'react';

interface ManageSubscriptionProps {
    subscription: (SubscriptionType & { membership_plan?: MembershipPlanType }) | null;
    plans: MembershipPlanType[];
}

export default function ManageSubscription({ subscription, plans }: ManageSubscriptionProps) {
    const changePlanForm = useForm({
        new_plan_id: '',
    });

    function handleCancel() {
        if (!subscription) return;
        router.post(`/subscription/${subscription.id}/cancel`);
    }

    function handleChangePlan(e: FormEvent) {
        e.preventDefault();
        if (!subscription) return;
        changePlanForm.post(`/subscription/${subscription.id}/change-plan`);
    }

    if (!subscription) {
        return (
            <AppLayout>
                <div className="mx-auto max-w-2xl">
                    <h1 className="mb-6 text-2xl font-bold text-gray-900">Manage Subscription</h1>
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p className="text-sm text-gray-600">You don't have an active subscription.</p>
                        <Link
                            href="/plans"
                            className="mt-4 inline-block rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                        >
                            Browse Plans
                        </Link>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const currentPlan = subscription.membership_plan;
    const otherPlans = plans.filter((p) => p.id !== subscription.membershipPlanId);

    return (
        <AppLayout>
            <div className="mx-auto max-w-2xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Manage Subscription</h1>

                <div className="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Current Subscription</h2>
                    <dl className="mt-4 space-y-3 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Plan</dt>
                            <dd className="font-medium text-gray-900">{currentPlan?.name ?? 'N/A'}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Status</dt>
                            <dd>
                                <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 capitalize">
                                    {subscription.status.replace('_', ' ')}
                                </span>
                            </dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Current period ends</dt>
                            <dd className="font-medium text-gray-900">
                                {new Date(subscription.endsAt).toLocaleDateString()}
                            </dd>
                        </div>
                    </dl>

                    <button
                        type="button"
                        onClick={handleCancel}
                        className="mt-6 w-full rounded-xl border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Cancel Subscription
                    </button>
                </div>

                {otherPlans.length > 0 && (
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900">Change Plan</h2>
                        <form onSubmit={handleChangePlan} className="space-y-4">
                            <div>
                                <label htmlFor="new_plan_id" className="mb-1 block text-sm font-medium text-gray-700">
                                    Select a new plan
                                </label>
                                <select
                                    id="new_plan_id"
                                    required
                                    value={changePlanForm.data.new_plan_id}
                                    onChange={(e) => changePlanForm.setData('new_plan_id', e.target.value)}
                                    className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    aria-describedby={changePlanForm.errors.new_plan_id ? 'new-plan-error' : undefined}
                                >
                                    <option value="">Choose a plan…</option>
                                    {otherPlans.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} — Rp {Number(p.price).toLocaleString('id-ID')}/{p.billingFrequency === 'monthly' ? 'mo' : 'yr'}
                                        </option>
                                    ))}
                                </select>
                                {changePlanForm.errors.new_plan_id && (
                                    <p id="new-plan-error" className="mt-1 text-sm text-red-600" role="alert">
                                        {changePlanForm.errors.new_plan_id}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={changePlanForm.processing}
                                className="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {changePlanForm.processing ? 'Changing…' : 'Change Plan'}
                            </button>
                        </form>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
