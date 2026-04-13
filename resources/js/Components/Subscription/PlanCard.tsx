import { Link } from '@inertiajs/react';
import type { MembershipPlanType } from '@/Types/subscription';

interface PlanCardProps {
    plan: MembershipPlanType;
    showSelectButton?: boolean;
}

export default function PlanCard({ plan, showSelectButton = true }: PlanCardProps) {
    return (
        <div className="flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">{plan.name}</h3>
            {plan.description && (
                <p className="mt-2 text-sm text-gray-600">{plan.description}</p>
            )}
            <div className="mt-4">
                <span className="text-3xl font-bold text-gray-900">
                    Rp {Number(plan.price).toLocaleString('id-ID')}
                </span>
                <span className="text-sm text-gray-500">
                    /{plan.billingFrequency === 'monthly' ? 'month' : 'year'}
                </span>
            </div>
            {showSelectButton && (
                <Link
                    href={`/checkout/${plan.id}`}
                    className="mt-6 block w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                    Select Plan
                </Link>
            )}
        </div>
    );
}
