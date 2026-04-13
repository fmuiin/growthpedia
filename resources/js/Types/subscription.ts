export interface MembershipPlanType {
    id: number;
    name: string;
    description: string | null;
    price: string;
    billingFrequency: 'monthly' | 'yearly';
    isActive: boolean;
}

export interface SubscriptionType {
    id: number;
    userId: number;
    membershipPlanId: number;
    status: 'active' | 'grace_period' | 'suspended' | 'cancelled';
    startsAt: string;
    endsAt: string;
    gracePeriodEndsAt: string | null;
    cancelledAt: string | null;
    plan?: MembershipPlanType;
}
