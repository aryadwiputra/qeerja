import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical } from 'lucide-react';
import { DropIndicator } from '@/components/board/drop-indicator';
import { TaskCard } from '@/components/task-card';
import type { BoardTaskItem } from '@/types/board';

interface Props {
    task: BoardTaskItem;
    isDragging?: boolean;
    onClick?: () => void;
    isOver?: boolean;
    edge?: 'top' | 'bottom' | null;
}

export function BoardSortableTask({
    task,
    isDragging,
    onClick,
    isOver,
    edge,
}: Props) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging: isSortableDragging,
    } = useSortable({ id: `task:${task.id}`, data: { type: 'task', task } });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isSortableDragging ? 0.3 : 1,
    };

    return (
        <div ref={setNodeRef} style={style} {...attributes}>
            <div className="group/task relative">
                {isOver && <DropIndicator edge={edge ?? null} />}
                <div
                    {...listeners}
                    className="absolute top-1/2 left-0 -translate-y-1/2 cursor-grab opacity-0 transition-opacity group-hover/task:opacity-100"
                >
                    <GripVertical className="size-3.5 text-muted-foreground" />
                </div>
                <div className="pl-0 transition-all group-hover/task:pl-4">
                    <TaskCard
                        task={task}
                        isDragging={isDragging}
                        onClick={onClick}
                    />
                </div>
            </div>
        </div>
    );
}
