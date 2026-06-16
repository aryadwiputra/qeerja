import { router } from '@inertiajs/react';
import {
    GitBranch,
    GitPullRequest,
    GitCommit,
    ExternalLink,
    Check,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface BranchInfoProps {
    task: {
        id: number;
        code: string;
        title: string;
        github_branch?: string | null;
    };
    project: {
        id: number;
        key: string;
        slug: string;
    };
    workspace: {
        id: number;
        slug: string;
    };
    hasIntegration: boolean;
    onCreateBranch?: (branch: string, url: string) => void;
}

interface PullRequestBadgeProps {
    pr: {
        number: number;
        title: string;
        state: 'open' | 'closed' | 'merged';
        author: string;
        url: string;
    };
}

interface Commit {
    sha: string;
    message: string;
    author: string;
    date: string;
    url: string;
}

interface CommitListProps {
    commits: Commit[];
    repoUrl?: string;
}

export function BranchInfo({
    task,
    project,
    workspace,
    hasIntegration,
    onCreateBranch,
}: BranchInfoProps) {
    const [creating, setCreating] = useState(false);

    const handleCreateBranch = async () => {
        setCreating(true);

        try {
            const response = await fetch(
                `/workspaces/${workspace.slug}/projects/${project.slug}/tasks/${task.id}/create-branch`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': decodeURIComponent(
                            document.cookie
                                .split('; ')
                                .find((row) => row.startsWith('XSRF-TOKEN='))
                                ?.split('=')[1] ?? '',
                        ),
                    },
                },
            );
            const data = await response.json();

            if (response.ok) {
                onCreateBranch?.(data.branch, data.url);
                router.reload({ only: ['task'] });
            }
        } catch {
            // Branch creation failed
        } finally {
            setCreating(false);
        }
    };

    if (task.github_branch) {
        return (
            <div className="flex items-center gap-2">
                <GitBranch className="h-4 w-4 text-muted-foreground" />
                <code className="rounded bg-muted px-2 py-1 text-sm">
                    {task.github_branch}
                </code>
            </div>
        );
    }

    if (hasIntegration) {
        return (
            <Button
                variant="outline"
                size="sm"
                onClick={handleCreateBranch}
                disabled={creating}
            >
                <GitBranch className="mr-2 h-4 w-4" />
                {creating ? 'Creating...' : 'Create Branch'}
            </Button>
        );
    }

    return null;
}

export function PullRequestBadge({ pr }: PullRequestBadgeProps) {
    const stateConfig = {
        open: {
            icon: GitPullRequest,
            color: 'text-green-600',
            badge: 'bg-green-100 text-green-700',
        },
        closed: {
            icon: X,
            color: 'text-red-600',
            badge: 'bg-red-100 text-red-700',
        },
        merged: {
            icon: Check,
            color: 'text-purple-600',
            badge: 'bg-purple-100 text-purple-700',
        },
    };

    const config = stateConfig[pr.state];
    const Icon = config.icon;

    return (
        <div className="flex items-center gap-2">
            <Badge className={cn('gap-1', config.badge)}>
                <Icon className="h-3 w-3" />
                PR #{pr.number}
            </Badge>
            <a
                href={pr.url}
                target="_blank"
                rel="noopener noreferrer"
                className="max-w-[200px] truncate text-sm text-muted-foreground hover:text-foreground"
            >
                {pr.title}
            </a>
            <span className="text-xs text-muted-foreground">
                by {pr.author}
            </span>
        </div>
    );
}

export function CommitList({ commits, repoUrl }: CommitListProps) {
    if (commits.length === 0) {
        return (
            <div className="py-4 text-center text-sm text-muted-foreground">
                No commits linked to this task
            </div>
        );
    }

    return (
        <div className="space-y-2">
            {commits.map((commit) => (
                <div
                    key={commit.sha}
                    className="flex items-start gap-3 rounded-lg p-2 hover:bg-muted/50"
                >
                    <GitCommit className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">
                                {commit.sha.substring(0, 7)}
                            </code>
                            <span className="truncate text-sm">
                                {commit.message}
                            </span>
                        </div>
                        <div className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                            <span>{commit.author}</span>
                            <span>·</span>
                            <span>
                                {new Date(commit.date).toLocaleDateString()}
                            </span>
                            {repoUrl && (
                                <>
                                    <span>·</span>
                                    <a
                                        href={`${repoUrl}/commit/${commit.sha}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:text-foreground"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                    </a>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
