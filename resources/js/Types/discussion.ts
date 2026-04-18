export interface CommentType {
    id: number;
    lessonId: number;
    userId: number;
    parentCommentId: number | null;
    content: string;
    isFlagged: boolean;
    flagReason: string | null;
    flaggedBy: number | null;
    isEdited: boolean;
    editedAt: string | null;
    createdAt: string;
    updatedAt: string;
    authorName: string;
    replies: CommentType[];
}

export interface PaginatedCommentsType {
    comments: CommentType[];
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}
