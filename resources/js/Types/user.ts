export interface User {
    id: number;
    name: string;
    email: string;
    role: 'learner' | 'admin';
    emailVerifiedAt: string | null;
}

export interface SharedBranding {
    siteName: string;
    logoUrl: string | null;
    primaryColor: string;
    secondaryColor: string;
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
    branding: SharedBranding;
    [key: string]: unknown;
}
