export interface CourseProgressType {
    enrollmentId: number;
    courseId: number;
    completionPercentage: number;
    completedCount: number;
    remainingCount: number;
    completedAt: string | null;
}
