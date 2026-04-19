export interface CatalogCourseType {
    id: number;
    title: string;
    descriptionSummary: string;
    instructorName: string;
    category: string;
    publishedAt: string;
}

export interface PaginatedCoursesType {
    data: CatalogCourseType[];
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}

export interface CatalogLessonOutlineType {
    id: number;
    title: string;
    contentType: 'text' | 'video' | 'mixed';
    sortOrder: number;
}

export interface CatalogModuleOutlineType {
    id: number;
    title: string;
    sortOrder: number;
    lessons: CatalogLessonOutlineType[];
}

export interface CatalogCourseDetailType {
    id: number;
    title: string;
    description: string;
    category: string;
    instructorName: string;
    instructorBio: string | null;
    publishedAt: string;
    modules: CatalogModuleOutlineType[];
    enrollmentCount: number;
    averageRating: number | null;
}

export interface CatalogFilters {
    category: string | null;
    sort: string | null;
}
