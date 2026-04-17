import { router } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import ProgressBar from '@/Components/Course/ProgressBar';
import type { CourseProgressType } from '@/Types/progress';
import type { CourseType } from '@/Types/course';

interface LearnerDashboardProps {
    progress: CourseProgressType;
    course: CourseType;
}

export default function LearnerDashboard({ progress, course }: LearnerDashboardProps) {
    function handleResume() {
        router.post(`/courses/${course.id}/resume`);
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-3xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">{course.title}</h1>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <ProgressBar percentage={progress.completionPercentage} />

                    <div className="mt-6 grid grid-cols-2 gap-4">
                        <div className="rounded-lg bg-green-50 p-4 text-center">
                            <p className="text-2xl font-bold text-green-700">{progress.completedCount}</p>
                            <p className="text-sm text-green-600">Completed</p>
                        </div>
                        <div className="rounded-lg bg-gray-50 p-4 text-center">
                            <p className="text-2xl font-bold text-gray-700">{progress.remainingCount}</p>
                            <p className="text-sm text-gray-600">Remaining</p>
                        </div>
                    </div>

                    {progress.completedAt ? (
                        <div className="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-center text-sm text-green-700">
                            Course completed on {new Date(progress.completedAt).toLocaleDateString()}
                        </div>
                    ) : (
                        <div className="mt-6">
                            <button
                                type="button"
                                onClick={handleResume}
                                className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Resume Course
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
