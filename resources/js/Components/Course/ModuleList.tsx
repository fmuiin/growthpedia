import { useState } from 'react';
import type { ModuleType, LessonType } from '@/Types/course';

interface ModuleListProps {
    modules: ModuleType[];
    currentLessonId?: number;
    onLessonSelect?: (lessonId: number) => void;
}

export default function ModuleList({ modules, currentLessonId, onLessonSelect }: ModuleListProps) {
    const [expandedModules, setExpandedModules] = useState<Set<number>>(() => {
        // Auto-expand the module containing the current lesson
        const set = new Set<number>();
        if (currentLessonId) {
            for (const mod of modules) {
                if (mod.lessons.some((l) => l.id === currentLessonId)) {
                    set.add(mod.id);
                }
            }
        } else {
            // Expand all by default
            for (const mod of modules) {
                set.add(mod.id);
            }
        }
        return set;
    });

    function toggleModule(moduleId: number) {
        setExpandedModules((prev) => {
            const next = new Set(prev);
            if (next.has(moduleId)) {
                next.delete(moduleId);
            } else {
                next.add(moduleId);
            }
            return next;
        });
    }

    function getLessonIcon(contentType: LessonType['contentType']) {
        switch (contentType) {
            case 'video':
                return (
                    <svg className="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                );
            case 'text':
                return (
                    <svg className="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                );
            case 'mixed':
                return (
                    <svg className="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                );
        }
    }

    const sortedModules = [...modules].sort((a, b) => a.sortOrder - b.sortOrder);

    return (
        <div className="flex flex-col">
            <h3 className="mb-4 text-xs font-bold tracking-wider text-gray-500 uppercase">
                Course Curriculum
            </h3>

            <div className="space-y-1">
                {sortedModules.map((mod, index) => {
                    const isExpanded = expandedModules.has(mod.id);
                    const sortedLessons = [...mod.lessons].sort((a, b) => a.sortOrder - b.sortOrder);

                    return (
                        <div key={mod.id}>
                            <button
                                type="button"
                                onClick={() => toggleModule(mod.id)}
                                className="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left hover:bg-gray-100"
                                aria-expanded={isExpanded}
                            >
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-semibold text-indigo-600">
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <span className="text-sm font-medium text-gray-900">
                                        {mod.title}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-gray-400">
                                        {sortedLessons.length} lesson{sortedLessons.length !== 1 ? 's' : ''}
                                    </span>
                                    <svg
                                        className={`h-4 w-4 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            {isExpanded && (
                                <ul className="ml-4 space-y-0.5 pb-2" role="list">
                                    {sortedLessons.map((lesson) => {
                                        const isCurrent = lesson.id === currentLessonId;
                                        return (
                                            <li key={lesson.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => onLessonSelect?.(lesson.id)}
                                                    className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition-colors ${
                                                        isCurrent
                                                            ? 'bg-indigo-50 font-medium text-indigo-700'
                                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                                    }`}
                                                    aria-current={isCurrent ? 'true' : undefined}
                                                >
                                                    {getLessonIcon(lesson.contentType)}
                                                    <span className="truncate">{lesson.title}</span>
                                                </button>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
