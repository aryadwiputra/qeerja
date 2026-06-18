'use no memo';

import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Zap, Trash2, Play, Pause } from 'lucide-react';
import { useState } from 'react';
import { FeatureGuide, InlineTooltip } from '@/components/feature-guide';
import type { GuideContent } from '@/components/feature-guide';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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

const automationGuide: GuideContent = {
    title: 'Automation Rules',
    description:
        'Automate task management by creating rules that trigger actions when specific events occur.',
    sections: [
        {
            title: 'How it works',
            content:
                'Each rule has three parts: a trigger event (when it fires), optional conditions (filter which tasks), and actions (what to do). Rules run in priority order (highest first).',
        },
        {
            title: 'Creating a rule',
            content:
                '1. Give your rule a descriptive name.\n2. Choose a trigger event — this determines when the rule fires.\n3. Add conditions (optional) — the rule only runs on tasks matching all conditions.\n4. Add one or more actions — what happens to matching tasks.\n5. Click "Create Rule" to save.',
        },
    ],
    items: [
        {
            heading: 'Trigger Events',
            data: [
                {
                    term: 'Task Created',
                    description:
                        'Fires when a new task is created in this project.',
                },
                {
                    term: 'Status Changed',
                    description:
                        'Fires when a task is moved to a different board column.',
                },
                {
                    term: 'Priority Changed',
                    description: "Fires when a task's priority is updated.",
                },
                {
                    term: 'Assignee Added',
                    description: 'Fires when someone is assigned to a task.',
                },
                {
                    term: 'Due Date Passed',
                    description:
                        'Fires when a task passes its due date (checked hourly).',
                },
            ],
        },
        {
            heading: 'Condition Fields',
            data: [
                {
                    term: 'Status',
                    description:
                        'The task\'s current board column status key (e.g., "in_progress", "done").',
                },
                {
                    term: 'Priority',
                    description:
                        'The task\'s current priority key (e.g., "high", "low").',
                },
                {
                    term: 'Assignee',
                    description:
                        'User ID(s) assigned to the task. Use "contains" to check if a specific user is assigned.',
                },
                {
                    term: 'Label',
                    description:
                        'Label ID(s) attached to the task. Use "contains" to check if a specific label is attached.',
                },
                {
                    term: 'Story Points',
                    description:
                        'The task\'s story point value. Use "greater_than" or "less_than" for numeric comparisons.',
                },
            ],
        },
        {
            heading: 'Condition Operators',
            data: [
                {
                    term: 'Equals / Not Equals',
                    description: 'Exact match or non-match.',
                },
                {
                    term: 'Contains / Not Contains',
                    description: 'Partial string match or non-match.',
                },
                {
                    term: 'Greater Than / Less Than',
                    description: 'Numeric comparison.',
                },
                {
                    term: 'In / Not In',
                    description:
                        'Value is in (or not in) a comma-separated list.',
                },
            ],
        },
        {
            heading: 'Action Types & Value Formats',
            data: [
                {
                    term: 'Assign User',
                    description: 'Format: user:{ID} — Example: user:5',
                },
                {
                    term: 'Add Label',
                    description: 'Format: label:{ID} — Example: label:3',
                },
                {
                    term: 'Remove Label',
                    description: 'Format: label:{ID} — Example: label:3',
                },
                {
                    term: 'Set Priority',
                    description: 'Format: priority:{ID} — Example: priority:2',
                },
                {
                    term: 'Move to Column',
                    description: 'Format: column:{ID} — Example: column:4',
                },
                {
                    term: 'Send Notification',
                    description:
                        'The message text sent to task assignees (or reporter if unassigned).',
                },
                {
                    term: 'Add Comment',
                    description:
                        'A comment posted by Automation Bot on the task.',
                },
            ],
        },
    ],
    tips: [
        'Use the "Test" button on any rule to preview how many tasks currently match its conditions.',
        'Higher priority rules run first. Adjust priority to control execution order.',
        "Disabled rules won't fire. Toggle them on/off with the play/pause button.",
        'Conditions are optional — a rule with no conditions matches all tasks for the chosen trigger.',
    ],
};

interface AutomationRule {
    id: number;
    name: string;
    enabled: boolean;
    trigger_event: string;
    conditions: Array<{
        field: string;
        operator: string;
        value: string;
    }> | null;
    actions: Array<{ type: string; value: string }> | null;
    priority: number;
    created_at: string;
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

interface Option {
    value: string;
    label: string;
}

interface Props {
    workspace: Workspace;
    project: ProjectData;
    rules: AutomationRule[];
    options: {
        trigger_events: Option[];
        condition_fields: Option[];
        condition_operators: Option[];
        action_types: Option[];
        board_columns: Array<{
            id: number;
            name: string;
            status_key: string;
            color: string | null;
        }>;
        priorities: Array<{
            id: number;
            name: string;
            key: string;
            color: string | null;
        }>;
        labels: Array<{
            id: number;
            name: string;
            slug: string;
            color: string | null;
        }>;
        members: Array<{ id: number; name: string; avatar: string | null }>;
    };
}

export default function AutomationIndex({
    workspace,
    project,
    rules: initialRules,
    options,
}: Props) {
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [newRule, setNewRule] = useState({
        name: '',
        trigger_event: '',
        conditions: [] as Array<{
            field: string;
            operator: string;
            value: string;
        }>,
        actions: [] as Array<{ type: string; value: string }>,
    });

    const handleCreateRule = (e: React.FormEvent) => {
        e.preventDefault();

        router.post(
            `/workspaces/${workspace.slug}/projects/${project.slug}/automation`,
            {
                name: newRule.name,
                trigger_event: newRule.trigger_event,
                conditions: newRule.conditions,
                actions: newRule.actions,
            },
            {
                onSuccess: () => {
                    setShowCreateDialog(false);
                    setNewRule({
                        name: '',
                        trigger_event: '',
                        conditions: [],
                        actions: [],
                    });
                },
            },
        );
    };

    const handleToggleRule = (rule: AutomationRule) => {
        router.put(
            `/workspaces/${workspace.slug}/projects/${project.slug}/automation/${rule.id}`,
            { enabled: !rule.enabled },
            { preserveScroll: true },
        );
    };

    const handleDeleteRule = (ruleId: number) => {
        if (!confirm('Delete this automation rule?')) {
            return;
        }

        router.delete(
            `/workspaces/${workspace.slug}/projects/${project.slug}/automation/${ruleId}`,
        );
    };

    const handleTestRule = (ruleId: number) => {
        router.post(
            `/workspaces/${workspace.slug}/projects/${project.slug}/automation/${ruleId}/test`,
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const result = (
                        page.props as {
                            test_result?: {
                                matching_count: number;
                                total_tasks: number;
                            };
                        }
                    ).test_result;

                    if (result) {
                        alert(
                            `Found ${result.matching_count} matching tasks out of ${result.total_tasks} total.`,
                        );
                    }
                },
            },
        );
    };

    const getTriggerLabel = (value: string) =>
        options.trigger_events.find((e) => e.value === value)?.label ?? value;

    const getActionLabel = (type: string) =>
        options.action_types.find((a) => a.value === type)?.label ?? type;

    return (
        <>
            <Head title={`Automation — ${project.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6">
                <div className="flex items-center gap-4">
                    <Link
                        href={projectShow.url({
                            workspace: workspace.slug,
                            project: project.slug,
                        })}
                        className="flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        <span>{project.name}</span>
                    </Link>
                    <span className="text-sm text-muted-foreground">/</span>
                    <span className="text-sm font-medium">Automation</span>
                </div>

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Automation Rules
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create rules to automate task management
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <FeatureGuide content={automationGuide} />
                        <Dialog
                            open={showCreateDialog}
                            onOpenChange={setShowCreateDialog}
                        >
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="mr-2 size-4" />
                                    New Rule
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-w-2xl">
                                <DialogHeader>
                                    <DialogTitle>
                                        Create Automation Rule
                                    </DialogTitle>
                                </DialogHeader>
                                <form
                                    onSubmit={handleCreateRule}
                                    className="space-y-4"
                                >
                                    <div>
                                        <Label>Rule Name</Label>
                                        <Input
                                            value={newRule.name}
                                            onChange={(e) =>
                                                setNewRule({
                                                    ...newRule,
                                                    name: e.target.value,
                                                })
                                            }
                                            placeholder="e.g., Auto-assign on status change"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label className="flex items-center gap-1.5">
                                            Trigger Event
                                            <InlineTooltip content="The event that fires this rule. Choose when you want the automation to run." />
                                        </Label>
                                        <Select
                                            value={newRule.trigger_event}
                                            onValueChange={(value) =>
                                                setNewRule({
                                                    ...newRule,
                                                    trigger_event: value,
                                                })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select trigger..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.trigger_events.map(
                                                    (event) => (
                                                        <SelectItem
                                                            key={event.value}
                                                            value={event.value}
                                                        >
                                                            {event.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label className="flex items-center gap-1.5">
                                            Conditions
                                            <InlineTooltip content="Optional filters. The rule only runs on tasks matching ALL conditions. Leave empty to match all tasks." />
                                        </Label>
                                        <div className="mt-2 space-y-2">
                                            {newRule.conditions.map(
                                                (condition, index) => (
                                                    <div
                                                        key={index}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <Select
                                                            value={
                                                                condition.field
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                const updated =
                                                                    [
                                                                        ...newRule.conditions,
                                                                    ];
                                                                updated[
                                                                    index
                                                                ].field = value;
                                                                setNewRule({
                                                                    ...newRule,
                                                                    conditions:
                                                                        updated,
                                                                });
                                                            }}
                                                        >
                                                            <SelectTrigger className="w-40">
                                                                <SelectValue placeholder="Field" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {options.condition_fields.map(
                                                                    (field) => (
                                                                        <SelectItem
                                                                            key={
                                                                                field.value
                                                                            }
                                                                            value={
                                                                                field.value
                                                                            }
                                                                        >
                                                                            {
                                                                                field.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>

                                                        <Select
                                                            value={
                                                                condition.operator
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                const updated =
                                                                    [
                                                                        ...newRule.conditions,
                                                                    ];
                                                                updated[
                                                                    index
                                                                ].operator =
                                                                    value;
                                                                setNewRule({
                                                                    ...newRule,
                                                                    conditions:
                                                                        updated,
                                                                });
                                                            }}
                                                        >
                                                            <SelectTrigger className="w-36">
                                                                <SelectValue placeholder="Operator" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {options.condition_operators.map(
                                                                    (op) => (
                                                                        <SelectItem
                                                                            key={
                                                                                op.value
                                                                            }
                                                                            value={
                                                                                op.value
                                                                            }
                                                                        >
                                                                            {
                                                                                op.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>

                                                        <Input
                                                            value={
                                                                condition.value
                                                            }
                                                            onChange={(e) => {
                                                                const updated =
                                                                    [
                                                                        ...newRule.conditions,
                                                                    ];
                                                                updated[
                                                                    index
                                                                ].value =
                                                                    e.target.value;
                                                                setNewRule({
                                                                    ...newRule,
                                                                    conditions:
                                                                        updated,
                                                                });
                                                            }}
                                                            placeholder="Value"
                                                            className="flex-1"
                                                        />

                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                const updated =
                                                                    newRule.conditions.filter(
                                                                        (
                                                                            _,
                                                                            i,
                                                                        ) =>
                                                                            i !==
                                                                            index,
                                                                    );
                                                                setNewRule({
                                                                    ...newRule,
                                                                    conditions:
                                                                        updated,
                                                                });
                                                            }}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </div>
                                                ),
                                            )}
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setNewRule({
                                                        ...newRule,
                                                        conditions: [
                                                            ...newRule.conditions,
                                                            {
                                                                field: 'status',
                                                                operator:
                                                                    'equals',
                                                                value: '',
                                                            },
                                                        ],
                                                    })
                                                }
                                            >
                                                Add Condition
                                            </Button>
                                        </div>
                                    </div>

                                    <div>
                                        <Label className="flex items-center gap-1.5">
                                            Actions
                                            <InlineTooltip content="What happens to matching tasks. You can add multiple actions — they all run in order." />
                                        </Label>
                                        <div className="mt-2 space-y-2">
                                            {newRule.actions.map(
                                                (action, index) => (
                                                    <div
                                                        key={index}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <Select
                                                            value={action.type}
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                const updated =
                                                                    [
                                                                        ...newRule.actions,
                                                                    ];
                                                                updated[
                                                                    index
                                                                ].type = value;
                                                                setNewRule({
                                                                    ...newRule,
                                                                    actions:
                                                                        updated,
                                                                });
                                                            }}
                                                        >
                                                            <SelectTrigger className="w-44">
                                                                <SelectValue placeholder="Action type" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {options.action_types.map(
                                                                    (type) => (
                                                                        <SelectItem
                                                                            key={
                                                                                type.value
                                                                            }
                                                                            value={
                                                                                type.value
                                                                            }
                                                                        >
                                                                            {
                                                                                type.label
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>

                                                        <Input
                                                            value={action.value}
                                                            onChange={(e) => {
                                                                const updated =
                                                                    [
                                                                        ...newRule.actions,
                                                                    ];
                                                                updated[
                                                                    index
                                                                ].value =
                                                                    e.target.value;
                                                                setNewRule({
                                                                    ...newRule,
                                                                    actions:
                                                                        updated,
                                                                });
                                                            }}
                                                            placeholder="Value (e.g., user:5, label:3)"
                                                            className="flex-1"
                                                        />
                                                        <InlineTooltip content="Format depends on action type: user:{ID}, label:{ID}, priority:{ID}, column:{ID}, or plain text for notifications/comments." />

                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => {
                                                                const updated =
                                                                    newRule.actions.filter(
                                                                        (
                                                                            _,
                                                                            i,
                                                                        ) =>
                                                                            i !==
                                                                            index,
                                                                    );
                                                                setNewRule({
                                                                    ...newRule,
                                                                    actions:
                                                                        updated,
                                                                });
                                                            }}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </div>
                                                ),
                                            )}
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setNewRule({
                                                        ...newRule,
                                                        actions: [
                                                            ...newRule.actions,
                                                            {
                                                                type: 'assign',
                                                                value: '',
                                                            },
                                                        ],
                                                    })
                                                }
                                            >
                                                Add Action
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setShowCreateDialog(false)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={
                                                !newRule.name.trim() ||
                                                !newRule.trigger_event
                                            }
                                        >
                                            Create Rule
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="space-y-3">
                    {initialRules.map((rule) => (
                        <Card key={rule.id}>
                            <CardContent className="p-4">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-start gap-3">
                                        <Zap
                                            className={`mt-1 size-5 ${rule.enabled ? 'text-yellow-500' : 'text-muted-foreground'}`}
                                        />
                                        <div>
                                            <h3 className="font-medium">
                                                {rule.name}
                                            </h3>
                                            <div className="mt-1 flex items-center gap-2">
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {getTriggerLabel(
                                                        rule.trigger_event,
                                                    )}
                                                </Badge>
                                                {rule.conditions &&
                                                    rule.conditions.length >
                                                        0 && (
                                                        <span className="text-xs text-muted-foreground">
                                                            {
                                                                rule.conditions
                                                                    .length
                                                            }{' '}
                                                            condition
                                                            {rule.conditions
                                                                .length !== 1
                                                                ? 's'
                                                                : ''}
                                                        </span>
                                                    )}
                                                {rule.actions &&
                                                    rule.actions.length > 0 && (
                                                        <span className="text-xs text-muted-foreground">
                                                            {
                                                                rule.actions
                                                                    .length
                                                            }{' '}
                                                            action
                                                            {rule.actions
                                                                .length !== 1
                                                                ? 's'
                                                                : ''}
                                                        </span>
                                                    )}
                                            </div>
                                            {rule.actions &&
                                                rule.actions.length > 0 && (
                                                    <div className="mt-2 flex flex-wrap gap-1">
                                                        {rule.actions.map(
                                                            (action, index) => (
                                                                <Badge
                                                                    key={index}
                                                                    variant="secondary"
                                                                    className="text-xs"
                                                                >
                                                                    {getActionLabel(
                                                                        action.type,
                                                                    )}
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                handleTestRule(rule.id)
                                            }
                                        >
                                            <Play className="mr-1 size-3" />
                                            Test
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                            onClick={() =>
                                                handleToggleRule(rule)
                                            }
                                        >
                                            {rule.enabled ? (
                                                <Pause className="size-4 text-yellow-500" />
                                            ) : (
                                                <Play className="size-4 text-muted-foreground" />
                                            )}
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                handleDeleteRule(rule.id)
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}

                    {initialRules.length === 0 && (
                        <div className="flex flex-col items-center justify-center gap-4 rounded-xl border py-16">
                            <Zap className="size-12 text-muted-foreground/40" />
                            <div className="text-center">
                                <p className="text-lg font-medium">
                                    No automation rules
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Create rules to automate task management.
                                </p>
                            </div>
                            <Button onClick={() => setShowCreateDialog(true)}>
                                <Plus className="mr-2 size-4" />
                                New Rule
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
