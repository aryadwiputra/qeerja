'use no memo';

import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { show as projectShow } from '@/routes/projects';
import {
    destroy as destroyComponent,
    store as storeComponent,
    update as updateComponent,
} from '@/routes/projects/components';

interface Member {
    id: number;
    name: string;
    avatar: string | null;
}

interface ComponentData {
    id: number;
    name: string;
    description: string | null;
    tasks_count: number;
    lead: Member | null;
}

interface Workspace {
    id: number;
    name: string;
    slug: string;
}

interface ProjectData {
    id: number;
    name: string;
    key: string;
    slug: string;
}

interface Props {
    workspace: Workspace;
    project: ProjectData;
    components: ComponentData[];
    members: Member[];
}

export default function ComponentsIndex({
    workspace,
    project,
    components,
    members,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<ComponentData | null>(null);
    const [deleting, setDeleting] = useState<ComponentData | null>(null);
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [leadId, setLeadId] = useState<string>('none');

    const openCreate = () => {
        setEditing(null);
        setName('');
        setDescription('');
        setLeadId('none');
        setDialogOpen(true);
    };

    const openEdit = (component: ComponentData) => {
        setEditing(component);
        setName(component.name);
        setDescription(component.description ?? '');
        setLeadId(String(component.lead?.id ?? 'none'));
        setDialogOpen(true);
    };

    const handleSubmit = () => {
        const data = {
            name,
            description: description || null,
            lead_id: leadId !== 'none' ? Number(leadId) : null,
        };

        if (editing) {
            router.put(
                updateComponent.url({
                    workspace: workspace.slug,
                    project: project.slug,
                    component: editing.id,
                }),
                data,
                { preserveScroll: true, onSuccess: () => setDialogOpen(false) },
            );
        } else {
            router.post(
                storeComponent.url({
                    workspace: workspace.slug,
                    project: project.slug,
                }),
                data,
                { preserveScroll: true, onSuccess: () => setDialogOpen(false) },
            );
        }
    };

    return (
        <>
            <Head title={`Components — ${project.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex items-center gap-4">
                    <Link
                        href={projectShow({
                            workspace: workspace.slug,
                            project: project.slug,
                        })}
                        className="flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        <span>{project.name}</span>
                    </Link>
                    <span className="text-sm text-muted-foreground">/</span>
                    <span className="text-sm text-muted-foreground">
                        Components
                    </span>
                </div>

                <div className="mx-auto w-full max-w-3xl">
                    <div className="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Components
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Categorize tasks by UI or backend components.
                            </p>
                        </div>
                        <Button size="sm" onClick={openCreate}>
                            <Plus className="size-3" />
                            Create component
                        </Button>
                    </div>

                    {components.length > 0 ? (
                        <div className="flex flex-col rounded-md border">
                            {components.map((component) => (
                                <div
                                    key={component.id}
                                    className="group flex items-center justify-between gap-4 border-b px-4 py-3 last:border-0 hover:bg-muted/50"
                                >
                                    <div className="min-w-0">
                                        <button
                                            type="button"
                                            className="text-sm font-medium hover:underline"
                                            onClick={() => openEdit(component)}
                                        >
                                            {component.name}
                                        </button>
                                        {component.description && (
                                            <p className="mt-0.5 line-clamp-1 text-xs text-muted-foreground">
                                                {component.description}
                                            </p>
                                        )}
                                        <div className="mt-1 flex items-center gap-3">
                                            <Badge
                                                variant="outline"
                                                className="text-xs"
                                            >
                                                {component.tasks_count} task
                                                {component.tasks_count !== 1
                                                    ? 's'
                                                    : ''}
                                            </Badge>
                                            {component.lead && (
                                                <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <Users className="size-3" />
                                                    {component.lead.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="hidden gap-1 group-hover:flex">
                                        <button
                                            type="button"
                                            className="rounded p-1 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                            title="Delete component"
                                            onClick={() =>
                                                setDeleting(component)
                                            }
                                        >
                                            <Trash2 className="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center gap-3 rounded-md border border-dashed py-16 text-center">
                            <Users className="size-8 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium">
                                    No components yet
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Create components to categorize tasks by
                                    area of the product.
                                </p>
                            </div>
                            <Button size="sm" onClick={openCreate}>
                                <Plus className="size-3" />
                                Create component
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Edit component' : 'Create component'}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="component-name">Name</Label>
                            <Input
                                id="component-name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="e.g. Frontend, API, Auth"
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="component-desc">Description</Label>
                            <Input
                                id="component-desc"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Optional description"
                            />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label>Lead</Label>
                            <Select value={leadId} onValueChange={setLeadId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Assign a lead..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No lead
                                    </SelectItem>
                                    {members.map((m) => (
                                        <SelectItem
                                            key={m.id}
                                            value={String(m.id)}
                                        >
                                            {m.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleSubmit} disabled={!name.trim()}>
                            {editing ? 'Save' : 'Create'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={!!deleting}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
                title="Delete component"
                description={
                    deleting
                        ? `Are you sure you want to delete "${deleting.name}"? Tasks will not be deleted, but they will be unlinked from this component.`
                        : ''
                }
                confirmText="Delete component"
                onConfirm={() => {
                    if (!deleting) {
                        return;
                    }

                    const id = deleting.id;
                    setDeleting(null);

                    router.delete(
                        destroyComponent.url({
                            workspace: workspace.slug,
                            project: project.slug,
                            component: id,
                        }),
                    );
                }}
            />
        </>
    );
}
