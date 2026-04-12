export interface User {
    id: number;
    name: string;
    email: string;
    role: 'learner' | 'instructor' | 'admin';
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}
