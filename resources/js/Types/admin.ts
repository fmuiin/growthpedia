export interface AdminUserType {
    id: number;
    name: string;
    email: string;
    role: 'learner' | 'instructor' | 'admin';
    subscriptionStatus: string | null;
    registrationDate: string;
    isSuspended: boolean;
}

export interface PaginatedAdminUsersType {
    users: AdminUserType[];
    total: number;
    currentPage: number;
    perPage: number;
    lastPage: number;
}

export interface DashboardMetricsType {
    totalLearnerCount: number;
    activeSubscriptionCount: number;
    totalCourseCount: number;
    totalRevenue: string;
}

export interface CourseAnalyticsType {
    courseId: number;
    courseTitle: string;
    enrollmentCount: number;
    averageCompletionPercentage: string;
    averageRating: string | null;
}

export interface FlaggedCommentType {
    id: number;
    content: string;
    flagReason: string;
    authorName: string;
    lessonTitle: string;
    flaggedAt: string;
}

export interface PaginatedFlaggedCommentsType {
    comments: FlaggedCommentType[];
    total: number;
    currentPage: number;
    perPage: number;
    lastPage: number;
}
