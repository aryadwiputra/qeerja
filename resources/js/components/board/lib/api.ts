import type { BoardColumn, ReorderBoardTasksPayload } from '@/types/board';

function getCsrfToken(): string {
    return decodeURIComponent(
        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
    );
}

export async function reorderBoardTasks(
    url: string,
    payload: ReorderBoardTasksPayload,
): Promise<BoardColumn[]> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const data = (await response.json().catch(() => null)) as {
            message?: string;
            errors?: Record<string, string[]>;
        } | null;
        const firstError = data?.errors
            ? Object.values(data.errors).flat()[0]
            : null;

        throw new Error(
            firstError ?? data?.message ?? 'Unable to reorder board tasks.',
        );
    }

    const data = (await response.json()) as { columns: BoardColumn[] };

    return data.columns;
}
