import { useDroppable } from '@dnd-kit/core';
import { EmptyColumnDropZone } from '@/components/board/empty-column-drop-zone';
import { cn } from '@/lib/utils';
import type { BoardColumn } from '@/types/board';

interface Props {
    column: BoardColumn;
    activeTaskId: number | null;
    isOverForReorder?: boolean;
    children: React.ReactNode;
}

export function BoardColumn({
    column,
    activeTaskId,
    isOverForReorder,
    children,
}: Props) {
    const { setNodeRef, isOver } = useDroppable({ id: `col:${column.id}` });
    const isEmpty = column.tasks.length === 0;
    const isDraggingTask = activeTaskId !== null;

    return (
        <div
            ref={setNodeRef}
            className={cn(
                'group flex min-h-0 w-[calc(100vw-2rem)] shrink-0 snap-start flex-col rounded-xl border border-border bg-card/70 transition-[background-color,border-color,box-shadow] sm:w-80',
                isOver && isDraggingTask && !isEmpty
                    ? 'border-dashed border-primary/50 bg-primary/[0.04]'
                    : isOver &&
                          isDraggingTask &&
                          isEmpty &&
                          'border-dashed border-primary/50 bg-primary/5 shadow-soft',
                isOverForReorder &&
                    'border-dashed border-primary/50 bg-primary/[0.06] shadow-soft',
            )}
        >
            {children}
            {isEmpty && (
                <EmptyColumnDropZone
                    isOver={isOver && isDraggingTask}
                    isDraggingTask={isDraggingTask}
                />
            )}
            {!isEmpty && isDraggingTask && (
                <EmptyColumnDropZone
                    variant="append"
                    isOver={isOver}
                    isDraggingTask={isDraggingTask}
                />
            )}
        </div>
    );
}
