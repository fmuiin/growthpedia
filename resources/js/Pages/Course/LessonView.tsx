import { router } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import LessonPlayer from '@/Components/Course/LessonPlayer';
import ModuleList from '@/Components/Course/ModuleList';
import CommentThread from '@/Components/Discussion/CommentThread';
import type { LessonType, CourseDetailType, ModuleType } from '@/Types/course';
import type { PaginatedCommentsType } from '@/Types/discussion';

interface LessonViewProps {
    lesson: LessonType & {
        module: ModuleType & {
            course: CourseDetailType;
        };
    };
    comments: PaginatedCommentsType;
    canComment: boolean;
}

export default function LessonView({ lesson, comments, canComment }: LessonViewProps) {
    const course = lesson.module.course;

    function handleLessonSelect(lessonId: number) {
        router.visit(`/lessons/${lessonId}`);
    }

    return (
        <AppLayout>
            <div className="flex flex-col gap-6 lg:flex-row">
                {/* Main content area */}
                <div className="min-w-0 flex-1 lg:w-2/3">
                    <LessonPlayer lesson={lesson} />

                    <div className="mt-6">
                        <h1 className="text-xl font-bold text-gray-900">{lesson.title}</h1>
                        <div className="mt-2 flex items-center gap-3">
                            <span className="rounded bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 capitalize">
                                {lesson.contentType}
                            </span>
                        </div>
                    </div>

                    {/* Discussion section */}
                    <div className="mt-8 border-t border-gray-200 pt-8">
                        <CommentThread
                            lessonId={lesson.id}
                            comments={comments}
                            canComment={canComment}
                        />
                    </div>
                </div>

                {/* Sidebar */}
                <aside className="w-full shrink-0 lg:w-80">
                    <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                        <ModuleList
                            modules={course.modules}
                            currentLessonId={lesson.id}
                            onLessonSelect={handleLessonSelect}
                        />
                    </div>
                </aside>
            </div>
        </AppLayout>
    );
}
