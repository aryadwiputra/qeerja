import { Form, Head } from '@inertiajs/react';
import NotificationPreferenceController from '@/actions/App/Http/Controllers/Settings/NotificationPreferenceController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/notifications';

interface NotificationPreference {
    label: string;
    in_app_enabled: boolean;
    email_enabled: boolean;
}

type Props = {
    preferences: Record<string, NotificationPreference>;
};

export default function NotificationSettings({ preferences }: Props) {
    return (
        <>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Notifications"
                    description="Manage how you receive notifications"
                />

                <Form
                    action={NotificationPreferenceController.update.url()}
                    method="put"
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing }) => (
                        <>
                            <div className="overflow-hidden rounded-lg border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-3 text-left font-medium">
                                                Event
                                            </th>
                                            <th className="px-4 py-3 text-center font-medium">
                                                In-app
                                            </th>
                                            <th className="px-4 py-3 text-center font-medium">
                                                Email
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(preferences).map(
                                            ([type, pref]) => (
                                                <tr
                                                    key={type}
                                                    className="border-b last:border-b-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        {pref.label}
                                                    </td>
                                                    <td className="px-4 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            name={`preferences[${type}][in_app_enabled]`}
                                                            defaultChecked={
                                                                pref.in_app_enabled
                                                            }
                                                            value="1"
                                                            className="size-4 rounded border-gray-300"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            name={`preferences[${type}][email_enabled]`}
                                                            defaultChecked={
                                                                pref.email_enabled
                                                            }
                                                            value="1"
                                                            className="size-4 rounded border-gray-300"
                                                        />
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-6 flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-notifications-button"
                                >
                                    Save preferences
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

NotificationSettings.layout = {
    breadcrumbs: [
        {
            title: 'Notification settings',
            href: edit(),
        },
    ],
};
