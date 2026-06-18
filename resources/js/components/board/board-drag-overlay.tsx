import { GripVertical } from 'lucide-react';
import { TaskCard } from '@/components/task-card';
import { Badge } from '@/components/ui/badge';
import type { BoardColumn, BoardTaskItem } from '@/types/board';

interface Props {
    task: BoardTaskItem | null;
    column: BoardColumn | null;
}

export function BoardDragOverlay({ task, column }: Props) {
    if (task) {
        return (
            <div className="w-72 rotate-2 rounded-xl shadow-elevated ring-1 ring-primary/20">
                <div className="mb-1 inline-flex rounded-full border bg-background px-2 py-0.5 text-[10px] font-medium text-muted-foreground shadow-sm">
                    Moving task
                </div>
                <TaskCard task={task} isDragging />
            </div>
        );
    }

    if (column) {
        return (
            <div className="w-72 rotate-1 rounded-xl border border-border bg-card shadow-elevated ring-1 ring-primary/20">
                <div className="flex items-center justify-between gap-3 px-3 py-2.5">
                    <div className="flex min-w-0 items-center gap-2">
                        <div
                            className="size-2.5 shrink-0 rounded-full"
                            style={{
                                backgroundColor: column.color ?? '#64748B',
                            }}
                        />
                        <h3 className="truncate text-sm font-semibold">
                            {column.name}
                        </h3>
                        <Badge
                            variant="secondary"
                            className="px-1.5 py-0 text-[10px]"
                        >
                            {column.tasks.length}
                        </Badge>
                    </div>
                    <GripVertical className="size-4 text-muted-foreground" />
                </div>
            </div>
        );
    }

    return null;
}
