import { toast } from 'sonner';

export function canAccessWorkspaceSettings(role?: string): boolean {
    return ['owner', 'admin'].includes(role ?? '');
}

export function canCreateProject(role?: string): boolean {
    return ['owner', 'admin', 'manager'].includes(role ?? '');
}

export function canAccessProjectSettings(role?: string | null): boolean {
    return ['lead', 'manager'].includes(role ?? '');
}

export function canAccessGoals(role?: string): boolean {
    return ['owner', 'admin', 'manager'].includes(role ?? '');
}

// ── Task ──

export function canEditTask(
    wsRole?: string,
    projectRole?: string | null,
): boolean {
    return (
        ['owner', 'admin', 'manager'].includes(wsRole ?? '') &&
        (projectRole === null ||
            ['lead', 'manager', 'developer'].includes(projectRole))
    );
}

export function canDeleteTask(
    wsRole?: string,
    projectRole?: string | null,
): boolean {
    return wsRole === 'owner' || projectRole === 'lead';
}

export function canComment(
    wsRole?: string,
    projectRole?: string | null,
): boolean {
    return (
        ['owner', 'admin', 'manager', 'member'].includes(wsRole ?? '') &&
        (projectRole === null ||
            ['lead', 'manager', 'developer', 'qa', 'member'].includes(
                projectRole,
            ))
    );
}

export function canCreateTask(wsRole?: string): boolean {
    return ['owner', 'admin', 'manager', 'member'].includes(wsRole ?? '');
}

// ── Epic / Sprint ──

export function canManageEpics(wsRole?: string): boolean {
    return ['owner', 'admin', 'manager'].includes(wsRole ?? '');
}

export const canManageSprints = canManageEpics;

// ── Labels / Board ──

export function canManageLabels(
    wsRole?: string,
    projectRole?: string | null,
): boolean {
    return (
        ['owner', 'admin', 'manager'].includes(wsRole ?? '') &&
        (projectRole === null || ['lead', 'manager'].includes(projectRole))
    );
}

export const canManageBoard = canManageLabels;

// ── Danger ──

export function canDeleteProject(
    wsRole?: string,
    projectRole?: string | null,
): boolean {
    return wsRole === 'owner' || projectRole === 'lead';
}

export function canDeleteWorkspace(role?: string): boolean {
    return role === 'owner';
}

export function toastNoAccess(): void {
    toast.error("You don't have permission to access this feature.");
}
