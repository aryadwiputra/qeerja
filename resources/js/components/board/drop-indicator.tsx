import { cn } from '@/lib/utils';

interface Props {
    edge: 'top' | 'bottom' | null;
}

export function DropIndicator({ edge }: Props) {
    if (!edge) {
        return null;
    }

    return (
        <div
            className={cn(
                'pointer-events-none absolute right-2 left-2 z-10 h-0.5 rounded-full bg-primary shadow-sm',
                edge === 'top' ? '-top-1' : '-bottom-1',
            )}
        />
    );
}
