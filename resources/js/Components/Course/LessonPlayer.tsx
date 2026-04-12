import type { LessonType } from '@/Types/course';

interface LessonPlayerProps {
    lesson: LessonType;
}

export default function LessonPlayer({ lesson }: LessonPlayerProps) {
    return (
        <div className="space-y-6">
            {(lesson.contentType === 'video' || lesson.contentType === 'mixed') && (
                <div className="relative aspect-video w-full overflow-hidden rounded-xl bg-gray-900">
                    {lesson.videoUrl ? (
                        <video
                            src={lesson.videoUrl}
                            controls
                            className="h-full w-full object-contain"
                            aria-label={`Video: ${lesson.title}`}
                        >
                            <track kind="captions" />
                            Your browser does not support the video element.
                        </video>
                    ) : (
                        <div className="flex h-full items-center justify-center">
                            <div className="text-center">
                                <svg
                                    className="mx-auto h-16 w-16 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={1.5}
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                    />
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={1.5}
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <p className="mt-2 text-sm text-gray-400">Video not available</p>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {(lesson.contentType === 'text' || lesson.contentType === 'mixed') && lesson.contentBody && (
                <div className="prose prose-indigo max-w-none rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div dangerouslySetInnerHTML={{ __html: lesson.contentBody }} />
                </div>
            )}

            {lesson.contentType === 'text' && !lesson.contentBody && (
                <div className="rounded-xl bg-white p-6 text-center text-gray-500 shadow-sm ring-1 ring-gray-200">
                    No content available for this lesson.
                </div>
            )}
        </div>
    );
}
