import { ArrowDownToLine } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

interface Props {
    isOver: boolean;
    isDraggingTask: boolean;
    variant?: 'empty' | 'append';
}

export function EmptyColumnDropZone({
    isOver,
    isDraggingTask,
    variant = 'empty',
}: Props) {
    const { t } = useTranslation();
    const isAppend = variant === 'append';

    return (
        <div
            className={cn(
                'mx-2 mb-2 flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border bg-muted/20 px-4 text-center transition-colors',
                isAppend ? 'min-h-14 py-3' : 'min-h-[200px] flex-1',
                isDraggingTask && 'border-primary/40 bg-primary/[0.03]',
                isOver && 'border-primary/70 bg-primary/[0.07]',
            )}
        >
            <ArrowDownToLine
                className={cn(
                    'text-muted-foreground',
                    isAppend ? 'size-4' : 'size-6',
                )}
            />
            <p className="text-xs font-medium text-muted-foreground">
                {isAppend
                    ? t('board.drop_here', 'Drop here')
                    : t('board.drop_task_here', 'Drop task here')}
            </p>
        </div>
    );
}
