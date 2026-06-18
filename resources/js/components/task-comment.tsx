import { useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface User {
    id: number;
    name: string;
    avatar: string | null;
}

interface MentionData {
    user_id: number;
    user_name: string;
    mentioned_text: string;
}

interface CommentData {
    id: number;
    body: string;
    created_at: string;
    edited_at: string | null;
    user: User;
    mentions?: MentionData[];
    replies?: Array<{
        id: number;
        body: string;
        created_at: string;
        user: User;
    }>;
}

export function TaskComment({
    comment,
    currentUserId,
    onUpdate,
    onDelete,
}: {
    comment: CommentData;
    currentUserId: number | null;
    onUpdate?: (id: number, body: string) => void;
    onDelete?: (id: number) => void;
}) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState(false);
    const [body, setBody] = useState(comment.body);
    const canManage = currentUserId === comment.user.id;

    const handleSave = () => {
        if (!body.trim()) {
            return;
        }

        onUpdate?.(comment.id, body);
        setEditing(false);
    };

    return (
        <div className="flex flex-col gap-2">
            <div className="flex items-start gap-3">
                <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium">
                    {comment.user.name.charAt(0).toUpperCase()}
                </div>
                <div className="flex min-w-0 flex-1 flex-col gap-1">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium">
                            {comment.user.name}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            {formatTimeAgo(comment.created_at)}
                        </span>
                        {comment.edited_at && (
                            <span className="text-xs text-muted-foreground italic">
                                {t('common.edited')}
                            </span>
                        )}
                    </div>
                    {editing ? (
                        <div className="flex flex-col gap-2">
                            <Input
                                value={body}
                                onChange={(event) =>
                                    setBody(event.target.value)
                                }
                                autoFocus
                            />
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={handleSave}
                                >
                                    {t('common.save')}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        setBody(comment.body);
                                        setEditing(false);
                                    }}
                                >
                                    {t('common.cancel')}
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm whitespace-pre-wrap">
                            {renderBodyWithMentions(
                                comment.body,
                                comment.mentions ?? [],
                            )}
                        </p>
                    )}
                    {canManage && (onDelete || onUpdate) && !editing && (
                        <div className="flex items-center gap-3">
                            {onUpdate && (
                                <button
                                    type="button"
                                    className="text-xs text-muted-foreground transition-colors hover:text-foreground"
                                    onClick={() => setEditing(true)}
                                >
                                    {t('common.edit')}
                                </button>
                            )}
                            {onDelete && (
                                <button
                                    type="button"
                                    className="text-xs text-muted-foreground transition-colors hover:text-destructive"
                                    onClick={() => onDelete(comment.id)}
                                >
                                    {t('common.delete')}
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>
            {comment.replies && comment.replies.length > 0 && (
                <div className="ml-8 flex flex-col gap-2 border-l-2 border-border pl-4">
                    {comment.replies.map((reply) => (
                        <div key={reply.id} className="flex items-start gap-3">
                            <div className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-[10px] font-medium">
                                {reply.user.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="flex flex-col gap-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-medium">
                                        {reply.user.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {formatTimeAgo(reply.created_at)}
                                    </span>
                                </div>
                                <p className="text-sm">{reply.body}</p>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

function renderBodyWithMentions(
    body: string,
    mentions: MentionData[],
): ReactNode {
    if (mentions.length === 0) {
        return body;
    }

    const escapedPatterns = mentions.map((m) =>
        m.mentioned_text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'),
    );
    const pattern = escapedPatterns.map((t) => `@${t}`).join('|');

    if (!pattern) {
        return body;
    }

    const regex = new RegExp(`(${pattern})`, 'g');
    const parts = body.split(regex);

    return parts.map((part, index) => {
        if (!part.startsWith('@')) {
            return <span key={index}>{part}</span>;
        }

        const mention = mentions.find((m) => `@${m.mentioned_text}` === part);

        if (mention) {
            return (
                <span key={index} className="font-medium text-primary">
                    {part}
                </span>
            );
        }

        return <span key={index}>{part}</span>;
    });
}

function formatTimeAgo(date: string): string {
    const now = new Date();
    const then = new Date(date);
    const diffMs = now.getTime() - then.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMins < 1) {
        return 'just now';
    }

    if (diffMins < 60) {
        return `${diffMins}m ago`;
    }

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    if (diffDays < 7) {
        return `${diffDays}d ago`;
    }

    return then.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
