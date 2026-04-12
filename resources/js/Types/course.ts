export interface LessonType {
    id: number;
    courseModuleId: number;
    title: string;
    contentType: 'text' | 'video' | 'mixed';
    contentBody: string | null;
    videoUrl: string | null;
    sortOrder: number;
}

export interface ModuleType {
    id: number;
    courseId: number;
    title: string;
    sortOrder: number;
    lessons: LessonType[];
}

export interface CourseType {
    id: number;
    instructorId: number;
    title: string;
    description: string;
    category: string;
    status: 'draft' | 'published' | 'unpublished';
    publishedAt: string | null;
    createdAt: string;
}

export interface CourseDetailType extends Omit<CourseType, 'createdAt'> {
    instructorName: string;
    modules: ModuleType[];
}
