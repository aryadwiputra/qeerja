import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as workspaceStore } from '@/routes/workspaces';

interface StepWorkspaceProps {
    onCreated: (workspace: { id: number; name: string; slug: string }) => void;
}

export function StepWorkspace({ onCreated }: StepWorkspaceProps) {
    const [slugManuallyEdited, setSlugManuallyEdited] = useState(false);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Create your workspace</CardTitle>
                <CardDescription>
                    A workspace is where your team collaborates on projects.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    action={workspaceStore()}
                    method="post"
                    className="flex flex-col gap-4"
                    onSuccess={(page) => {
                        const workspace = (
                            page.props as Record<string, unknown>
                        ).flash as
                            | {
                                  workspace?: {
                                      id: number;
                                      name: string;
                                      slug: string;
                                  };
                              }
                            | undefined;

                        if (workspace?.workspace) {
                            onCreated(workspace.workspace);
                        } else {
                            window.location.reload();
                        }
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="name">Workspace name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder="My Team"
                                    required
                                    onChange={(e) => {
                                        if (!slugManuallyEdited) {
                                            const slugInput =
                                                document.getElementById(
                                                    'slug',
                                                ) as HTMLInputElement;

                                            if (slugInput) {
                                                slugInput.value = e.target.value
                                                    .toLowerCase()
                                                    .replace(/\s+/g, '-')
                                                    .replace(/[^a-z0-9-]/g, '');
                                            }
                                        }
                                    }}
                                    data-invalid={!!errors.name}
                                    aria-invalid={!!errors.name}
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    placeholder="my-team"
                                    required
                                    onFocus={() => setSlugManuallyEdited(true)}
                                    data-invalid={!!errors.slug}
                                    aria-invalid={!!errors.slug}
                                />
                                {errors.slug && (
                                    <p className="text-sm text-destructive">
                                        {errors.slug}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="description">
                                    Description{' '}
                                    <span className="text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="description"
                                    name="description"
                                    placeholder="A short description of your workspace"
                                />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full"
                            >
                                {processing
                                    ? 'Creating...'
                                    : 'Create workspace'}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
