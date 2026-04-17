interface ProgressBarProps {
    percentage: number;
}

export default function ProgressBar({ percentage }: ProgressBarProps) {
    const clampedPercentage = Math.min(100, Math.max(0, percentage));
    const roundedPercentage = Math.round(clampedPercentage * 100) / 100;

    return (
        <div className="w-full">
            <div className="mb-1 flex items-center justify-between">
                <span className="text-sm font-medium text-gray-700">Progress</span>
                <span className="text-sm font-medium text-indigo-600">{roundedPercentage}%</span>
            </div>
            <div
                className="h-3 w-full overflow-hidden rounded-full bg-gray-200"
                role="progressbar"
                aria-valuenow={roundedPercentage}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={`Course progress: ${roundedPercentage}%`}
            >
                <div
                    className="h-full rounded-full bg-indigo-600 transition-all duration-300"
                    style={{ width: `${clampedPercentage}%` }}
                />
            </div>
        </div>
    );
}
