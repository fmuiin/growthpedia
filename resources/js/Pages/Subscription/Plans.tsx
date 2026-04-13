import AppLayout from '@/Components/Layout/AppLayout';
import PlanCard from '@/Components/Subscription/PlanCard';
import type { MembershipPlanType } from '@/Types/subscription';

interface PlansProps {
    plans: MembershipPlanType[];
}

export default function Plans({ plans }: PlansProps) {
    return (
        <AppLayout>
            <div className="mx-auto max-w-4xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">Membership Plans</h1>

                {plans.length === 0 ? (
                    <p className="text-sm text-gray-500">No plans available at the moment.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {plans.map((plan) => (
                            <PlanCard key={plan.id} plan={plan} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
