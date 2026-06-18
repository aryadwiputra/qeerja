'use no memo';

import { Bookmark, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    index as savedFilterIndex,
    store as savedFilterStore,
    destroy as savedFilterDestroy,
} from '@/routes/projects/saved-filters';

interface SavedFilter {
    id: number;
    name: string;
    filters: Record<string, unknown>;
    sort_field: string;
    sort_direction: string;
    is_shared: boolean;
    user_id: number;
}

interface Props {
    workspaceSlug: string;
    projectSlug: string;
    currentFilters: Record<string, unknown>;
    currentSort: { field: string; direction: string };
    onLoad: (filter: SavedFilter) => void;
}

export function SavedFilterDropdown({
    workspaceSlug,
    projectSlug,
    currentFilters,
    currentSort,
    onLoad,
}: Props) {
    const { t } = useTranslation();
    const [filters, setFilters] = useState<SavedFilter[]>([]);
    const [saveName, setSaveName] = useState('');
    const [saving, setSaving] = useState(false);
    const [open, setOpen] = useState(false);
    const abortRef = useRef<AbortController | null>(null);

    const fetchFilters = useCallback(() => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        fetch(
            savedFilterIndex.url({
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
            .then((data) => {
                setFilters(data.saved_filters ?? []);
            })
            .catch(() => {});

        return () => controller.abort();
    }, [workspaceSlug, projectSlug]);

    useEffect(() => {
        if (open) {
            const cleanup = fetchFilters();

            return cleanup;
        }
    }, [open, fetchFilters]);

    const handleSave = () => {
        if (!saveName.trim()) {
            return;
        }

        setSaving(true);

        fetch(
            savedFilterStore.url({
                workspace: workspaceSlug,
                project: projectSlug,
            }),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement | null
                        )?.content ?? '',
                },
                body: JSON.stringify({
                    name: saveName.trim(),
                    filters: currentFilters,
                    sort_field: currentSort.field,
                    sort_direction: currentSort.direction,
                }),
            },
        )
            .then((r) => r.json())
            .then((data) => {
                setFilters((prev) => [
                    ...prev,
                    {
                        id: data.id,
                        name: data.name,
                        filters: data.filters,
                        sort_field: data.sort_field,
                        sort_direction: data.sort_direction,
                        is_shared: data.is_shared,
                        user_id: 0,
                    },
                ]);
                setSaveName('');
                setSaving(false);
            })
            .catch(() => {
                setSaving(false);
            });
    };

    const handleDelete = (filterId: number) => {
        if (!confirm(t('filter.delete_filter'))) {
            return;
        }

        fetch(
            savedFilterDestroy.url({
                workspace: workspaceSlug,
                project: projectSlug,
                savedFilter: filterId,
            }),
            {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement | null
                        )?.content ?? '',
                },
            },
        ).then(() => {
            setFilters((prev) => prev.filter((f) => f.id !== filterId));
        });
    };

    const hasActiveFilters =
        Object.keys(currentFilters).length > 0 ||
        currentSort.field !== 'position' ||
        currentSort.direction !== 'asc';

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                    <Bookmark className="mr-1.5 size-3.5" />
                    <span>{t('filter.saved_filters')}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
                {filters.length > 0 ? (
                    filters.map((filter) => (
                        <DropdownMenuItem
                            key={filter.id}
                            className="flex items-center justify-between"
                        >
                            <button
                                type="button"
                                className="flex flex-1 items-center gap-2 text-left"
                                onClick={() => {
                                    onLoad(filter);
                                    setOpen(false);
                                }}
                            >
                                <span className="truncate">{filter.name}</span>
                                {filter.is_shared && (
                                    <span className="shrink-0 rounded bg-muted px-1 py-0.5 text-[9px] text-muted-foreground">
                                        Shared
                                    </span>
                                )}
                            </button>
                            <button
                                type="button"
                                className="ml-2 shrink-0 rounded p-1 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    handleDelete(filter.id);
                                }}
                            >
                                <Trash2 className="size-3" />
                            </button>
                        </DropdownMenuItem>
                    ))
                ) : (
                    <div className="px-2 py-1.5 text-xs text-muted-foreground">
                        {t('filter.no_saved_filters')}
                    </div>
                )}

                {hasActiveFilters && (
                    <>
                        <DropdownMenuSeparator />
                        <div className="flex items-center gap-2 px-2 py-1.5">
                            <Input
                                value={saveName}
                                onChange={(e) => setSaveName(e.target.value)}
                                placeholder={t('filter.filter_name')}
                                className="h-7 text-xs"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleSave();
                                    }
                                }}
                            />
                            <Button
                                variant="default"
                                size="sm"
                                className="h-7 shrink-0"
                                onClick={handleSave}
                                disabled={!saveName.trim() || saving}
                            >
                                {t('common.save')}
                            </Button>
                        </div>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
