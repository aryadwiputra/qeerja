import { Loader2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { index as reportsIndex } from '@/routes/projects/reports';

interface StatusSummary {
    key: string;
    name: string;
    color: string | null;
    count: number;
}

interface AssigneeLoad {
    name: string;
    avatar: string | null;
    total: number;
    completed: number;
}

interface BurndownPoint {
    date: string;
    remaining: number;
    ideal: number;
}

interface ReportData {
    summary: {
        total: number;
        completed: number;
        overdue: number;
        no_due_date: number;
        completion_rate: number;
        by_status: StatusSummary[];
    };
    assignee_workload: AssigneeLoad[];
    burndown: {
        sprint: {
            name: string;
            start_date: string | null;
            end_date: string | null;
        };
        data: BurndownPoint[];
    } | null;
}

interface ReportsTabProps {
    workspaceSlug: string;
    projectSlug: string;
}

export function ReportsTab({ workspaceSlug, projectSlug }: ReportsTabProps) {
    const [data, setData] = useState<ReportData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        /* eslint-disable react-hooks/set-state-in-effect */
        setLoading(true);
        setError(null);
        /* eslint-enable react-hooks/set-state-in-effect */

        fetch(
            reportsIndex.url({
                workspace: workspaceSlug,
                project: projectSlug,
            }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            },
        )
            .then((r) => r.json())
            .then(setData)
            .catch((err: unknown) => {
                if ((err as Error)?.name !== 'AbortError') {
                    setError('Failed to load reports.');
                }
            })
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, [workspaceSlug, projectSlug]);

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <Loader2 className="size-6 animate-spin text-muted-foreground" />
            </div>
        );
    }

    if (error || !data) {
        return (
            <div className="flex items-center justify-center py-20">
                <p className="text-sm text-muted-foreground">
                    {error ?? 'No data.'}
                </p>
            </div>
        );
    }

    const maxStatusCount = Math.max(
        ...data.summary.by_status.map((s) => s.count),
        1,
    );

    return (
        <div className="flex flex-col gap-6">
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <SummaryCard label="Total tasks" value={data.summary.total} />
                <SummaryCard label="Completed" value={data.summary.completed} />
                <SummaryCard
                    label="Overdue"
                    value={data.summary.overdue}
                    className={
                        data.summary.overdue > 0 ? 'text-destructive' : ''
                    }
                />
                <SummaryCard
                    label="Completion rate"
                    value={`${data.summary.completion_rate}%`}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-sm font-semibold">
                        Tasks by status
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-col gap-3">
                    {data.summary.by_status.map((s) => (
                        <div key={s.key} className="flex items-center gap-3">
                            <span className="w-28 shrink-0 text-sm">
                                {s.name}
                            </span>
                            <div className="flex h-5 flex-1 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full transition-all"
                                    style={{
                                        width: `${(s.count / maxStatusCount) * 100}%`,
                                        backgroundColor:
                                            s.color ?? 'var(--primary)',
                                    }}
                                />
                            </div>
                            <span className="w-10 text-right text-sm font-medium tabular-nums">
                                {s.count}
                            </span>
                        </div>
                    ))}
                    {data.summary.by_status.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No tasks yet.
                        </p>
                    )}
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">
                            Assignee workload
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        {data.assignee_workload.map((a) => (
                            <div
                                key={a.name}
                                className="flex items-center gap-3"
                            >
                                <span className="w-28 shrink-0 truncate text-sm">
                                    {a.name}
                                </span>
                                <div className="flex h-5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full rounded-full bg-primary transition-all"
                                        style={{
                                            width: `${(a.completed / Math.max(a.total, 1)) * 100}%`,
                                        }}
                                    />
                                </div>
                                <span className="w-20 text-right text-sm text-muted-foreground tabular-nums">
                                    {a.completed}/{a.total}
                                </span>
                            </div>
                        ))}
                        {data.assignee_workload.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No assigned tasks.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {data.burndown && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-semibold">
                                Burndown — {data.burndown.sprint.name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <BurndownChart data={data.burndown.data} />
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}

function SummaryCard({
    label,
    value,
    className,
}: {
    label: string;
    value: string | number;
    className?: string;
}) {
    return (
        <Card>
            <CardContent className="pt-5">
                <p className="text-xs tracking-wider text-muted-foreground uppercase">
                    {label}
                </p>
                <p
                    className={cn(
                        'mt-1 text-2xl font-bold tabular-nums',
                        className,
                    )}
                >
                    {value}
                </p>
            </CardContent>
        </Card>
    );
}

function BurndownChart({ data }: { data: BurndownPoint[] }) {
    const padding = { top: 20, right: 16, bottom: 28, left: 40 };
    const width = 400;
    const height = 200;
    const chartW = width - padding.left - padding.right;
    const chartH = height - padding.top - padding.bottom;

    const maxVal = Math.max(
        ...data.map((d) => d.remaining),
        ...data.map((d) => d.ideal),
        1,
    );
    const minDate = new Date(data[0]?.date ?? 0).getTime();
    const maxDate = new Date(data[data.length - 1]?.date ?? 0).getTime();
    const dateRange = Math.max(maxDate - minDate, 1);

    const xScale = (date: string) =>
        ((new Date(date).getTime() - minDate) / dateRange) * chartW +
        padding.left;
    const yScale = (val: number) =>
        padding.top + chartH - (val / maxVal) * chartH;

    const idealLine = data
        .map((d) => `${xScale(d.date)},${yScale(d.ideal)}`)
        .join(' ');
    const actualLine = data
        .map((d) => `${xScale(d.date)},${yScale(d.remaining)}`)
        .join(' ');

    const yTicks = [0, Math.round(maxVal / 2), maxVal];

    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            className="w-full max-w-full"
            style={{ fontFamily: 'monospace' }}
        >
            <line
                x1={padding.left}
                y1={padding.top + chartH}
                x2={padding.left + chartW}
                y2={padding.top + chartH}
                stroke="currentColor"
                className="text-border"
                strokeWidth={1}
            />
            <line
                x1={padding.left}
                y1={padding.top}
                x2={padding.left}
                y2={padding.top + chartH}
                stroke="currentColor"
                className="text-border"
                strokeWidth={1}
            />
            {yTicks.map((tick) => (
                <g key={tick}>
                    <line
                        x1={padding.left}
                        y1={yScale(tick)}
                        x2={padding.left + chartW}
                        y2={yScale(tick)}
                        stroke="currentColor"
                        className="text-muted/60"
                        strokeWidth={1}
                        strokeDasharray="4 2"
                    />
                    <text
                        x={padding.left - 6}
                        y={yScale(tick) + 3}
                        textAnchor="end"
                        className="fill-muted-foreground"
                        fontSize={10}
                    >
                        {tick}
                    </text>
                </g>
            ))}
            <polyline
                points={idealLine}
                fill="none"
                className="stroke-muted-foreground/40"
                strokeWidth={2}
                strokeDasharray="6 3"
            />
            <polyline
                points={actualLine}
                fill="none"
                className="stroke-primary"
                strokeWidth={2}
            />
            {data
                .filter(
                    (_, i) =>
                        i % Math.max(1, Math.floor(data.length / 6)) === 0 ||
                        i === data.length - 1,
                )
                .map((d) => (
                    <text
                        key={d.date}
                        x={xScale(d.date)}
                        y={padding.top + chartH + 16}
                        textAnchor="middle"
                        className="fill-muted-foreground"
                        fontSize={9}
                    >
                        {d.date.slice(5)}
                    </text>
                ))}
            <text
                x={padding.left + 4}
                y={padding.top - 6}
                className="fill-muted-foreground"
                fontSize={9}
            >
                Tasks
            </text>
        </svg>
    );
}
