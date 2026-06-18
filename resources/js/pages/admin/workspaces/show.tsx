import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, User } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface Member {
    id: number;
    user_id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    joined_at: string | null;
}

interface Props {
    workspace: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        status: string;
        created_at: string;
    };
    members: Member[];
}

export default function AdminWorkspaceShow({ workspace, members }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={workspace.name} />

            <Link
                href="/admin/workspaces"
                className="mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="size-4" />
                {t('workspace.back_to_workspaces')}
            </Link>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="size-5" />
                                {workspace.name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex items-center gap-2">
                                <span className="w-24 text-muted-foreground">
                                    {t('admin.slug')}:
                                </span>
                                <span>{workspace.slug}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="w-24 text-muted-foreground">
                                    {t('admin.status')}:
                                </span>
                                <span className="text-xs text-green-600 dark:text-green-400">
                                    {t('admin.active')}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="w-24 text-muted-foreground">
                                    {t('admin.created')}:
                                </span>
                                <span>{workspace.created_at}</span>
                            </div>
                            {workspace.description && (
                                <div className="flex items-start gap-2">
                                    <span className="w-24 shrink-0 text-muted-foreground">
                                        {t('workspace.description_label')}:
                                    </span>
                                    <span>{workspace.description}</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <User className="size-4" />
                                {t('admin.members')} ({members.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('admin.name')}</TableHead>
                                        <TableHead>
                                            {t('admin.email')}
                                        </TableHead>
                                        <TableHead>{t('admin.role')}</TableHead>
                                        <TableHead>
                                            {t('admin.status')}
                                        </TableHead>
                                        <TableHead>
                                            {t('admin.joined')}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {members.map((member) => (
                                        <TableRow key={member.id}>
                                            <TableCell className="font-medium">
                                                {member.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {member.email}
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-xs capitalize">
                                                    {member.role}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                {member.status === 'active' ? (
                                                    <span className="text-xs text-green-600 dark:text-green-400">
                                                        {t('admin.active')}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        {member.status}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {member.joined_at ?? 'N/A'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
