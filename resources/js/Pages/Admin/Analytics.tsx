import AdminLayout from '@/Components/Layout/AdminLayout';
import { router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { DashboardMetricsType } from '@/Types/admin';

interface AnalyticsProps {
    metrics: DashboardMetricsType;
    startDate: string;
    endDate: string;
}

export default function Analytics({ metrics, startDate, endDate }: AnalyticsProps) {
    const [start, setStart] = useState(startDate);
    const [end, setEnd] = useState(endDate);

    function handleFilter(e: FormEvent) {
        e.preventDefault();
        router.get('/admin/analytics', {
            start_date: start,
            end_date: end,
        });
    }

    function handleExportCsv() {
        window.location.href = `/admin/analytics/export?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
    }

    const metricCards: { label: string; value: string | number; description: string }[] = [
        {
            label: 'Total Learners',
            value: metrics.totalLearnerCount.toLocaleString(),
            description: 'Registered learner accounts',
        },
        {
            label: 'Active Subscriptions',
            value: metrics.activeSubscriptionCount.toLocaleString(),
            description: 'Currently active subscriptions',
        },
        {
            label: 'Total Courses',
            value: metrics.totalCourseCount.toLocaleString(),
            description: 'Published courses on the platform',
        },
        {
            label: 'Total Revenue',
            value: `Rp ${Number(metrics.totalRevenue).toLocaleString()}`,
            description: 'Revenue for the selected period',
        },
    ];

    return (
        <AdminLayout>
            <div className="mx-auto max-w-6xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
                    <button
                        onClick={handleExportCsv}
                        className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Export CSV
                    </button>
                </div>

                {/* Date range filter */}
                <form onSubmit={handleFilter} className="mb-8 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <fieldset>
                        <legend className="mb-3 text-sm font-medium text-gray-700">Date Range</legend>
                        <div className="flex flex-wrap items-end gap-4">
                            <div>
                                <label htmlFor="start-date" className="mb-1 block text-sm text-gray-600">
                                    Start Date
                                </label>
                                <input
                                    id="start-date"
                                    type="date"
                                    value={start}
                                    onChange={(e) => setStart(e.target.value)}
                                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label htmlFor="end-date" className="mb-1 block text-sm text-gray-600">
                                    End Date
                                </label>
                                <input
                                    id="end-date"
                                    type="date"
                                    value={end}
                                    onChange={(e) => setEnd(e.target.value)}
                                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <button
                                type="submit"
                                className="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Apply Filter
                            </button>
                        </div>
                    </fieldset>
                </form>

                {/* Metrics cards */}
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {metricCards.map((card) => (
                        <div
                            key={card.label}
                            className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <p className="text-sm font-medium text-gray-500">{card.label}</p>
                            <p className="mt-2 text-3xl font-bold text-gray-900">{card.value}</p>
                            <p className="mt-1 text-xs text-gray-400">{card.description}</p>
                        </div>
                    ))}
                </div>

                {/* Period info */}
                <p className="mt-6 text-sm text-gray-500">
                    Showing data from{' '}
                    <time dateTime={startDate} className="font-medium text-gray-700">
                        {new Date(startDate).toLocaleDateString()}
                    </time>
                    {' '}to{' '}
                    <time dateTime={endDate} className="font-medium text-gray-700">
                        {new Date(endDate).toLocaleDateString()}
                    </time>
                </p>
            </div>
        </AdminLayout>
    );
}
