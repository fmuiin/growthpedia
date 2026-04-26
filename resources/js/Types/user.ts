export interface User {
    id: number;
    name: string;
    email: string;
    role: 'learner' | 'instructor' | 'admin';
    emailVerifiedAt: string | null;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    subscription: {
        isActive: boolean;
    } | null;
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}
