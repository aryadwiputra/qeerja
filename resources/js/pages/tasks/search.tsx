import { Head, router } from '@inertiajs/react';
import { SearchX } from 'lucide-react';
import { useState } from 'react';
import { TaskDetailDrawer } from '@/components/task-detail-drawer';
import { TaskSearchFilters } from '@/components/task-search-filters';
import type {
    TaskSearchFilters as TaskSearchFiltersState,
    TaskSearchOptions,
} from '@/components/task-search-filters';
import { TaskSearchResult } from '@/components/task-search-result';
import type { TaskSearchResultItem } from '@/components/task-search-result';
import { Button } from '@/components/ui/button';

interface Props {
    tasks: {
        data: TaskSearchResultItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: TaskSearchFiltersState;
    options: TaskSearchOptions;
}

export default function TaskSearch({ tasks, filters, options }: Props) {
    const [drawerTask, setDrawerTask] = useState<TaskSearchResultItem | null>(
        null,
    );
    const [drawerOpen, setDrawerOpen] = useState(false);

    const openTask = (task: TaskSearchResultItem) => {
        setDrawerTask(task);
        setDrawerOpen(true);
    };

    return (
        <>
            <Head title="Task Search" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Task Search
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Search tasks across projects you can access.
                        </p>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {tasks.total} {tasks.total === 1 ? 'result' : 'results'}
                    </p>
                </div>

                <TaskSearchFilters filters={filters} options={options} />

                {tasks.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 rounded-xl border py-20">
                        <SearchX className="size-12 text-muted-foreground/40" />
                        <div className="text-center">
                            <p className="text-lg font-medium">
                                No matching tasks
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Try changing the search term or filters.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {tasks.data.map((task) => (
                            <TaskSearchResult
                                key={task.id}
                                task={task}
                                onOpen={openTask}
                            />
                        ))}
                    </div>
                )}

                {tasks.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2 py-2">
                        {tasks.links
                            .filter(
                                (link) =>
                                    !link.label.includes('Previous') &&
                                    !link.label.includes('Next'),
                            )
                            .map((link, index) =>
                                link.url ? (
                                    <Button
                                        key={index}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        className="h-8 min-w-8"
                                        onClick={() =>
                                            router.visit(link.url!, {
                                                preserveScroll: true,
                                                preserveState: true,
                                            })
                                        }
                                    >
                                        {link.label
                                            .replace(/&laquo;|&raquo;/g, '')
                                            .trim()}
                                    </Button>
                                ) : (
                                    <span
                                        key={index}
                                        className="px-2 text-sm text-muted-foreground"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ),
                            )}
                    </div>
                )}
            </div>

            <TaskDetailDrawer
                workspaceSlug={drawerTask?.workspace.slug ?? null}
                projectSlug={drawerTask?.project.slug ?? null}
                taskId={drawerTask?.id ?? null}
                open={drawerOpen}
                onOpenChange={setDrawerOpen}
                onDelete={() => router.reload()}
            />
        </>
    );
}
